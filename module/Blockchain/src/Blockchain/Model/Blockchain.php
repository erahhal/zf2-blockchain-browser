<?php

namespace Blockchain\Model;

class Blockchain
{
    protected static $hexChars = '0123456789ABCDEF';
    protected static $base58chars = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';

    protected $objectManager;
    protected $bitcoindServerUrl;

    public function __construct($options = null)
    {/*{{{*/
        if (isset($options['objectManager'])) {
            $this->objectManager = $options['objectManager'];
        }
        if (isset($options['bitcoindServerUrl'])) {
            $this->bitcoindServerUrl = $options['bitcoindServerUrl'];
        }
    }/*}}}*/

    /* interesting queries:

       Blocks with most transactions:
       select b.blocknumber, count(*) as blockCount from Transaction t left join Block b on (t.block_id = b.id) group by t.block_id order by blockCount desc limit 10;

       Transactions with most outputs:
       select t.txid, count(*) as txCount from Output o left join Transaction t on (o.transaction_id = t.id) group by o.transaction_id order by txCount desc limit 10;

       Transactions with most inputs:
       select t.txid, count(*) as txCount from Input i left join Transaction t on (i.transaction_id = t.id) group by i.transaction_id order by txCount desc limit 10;

       Addresses with most receives:
       select k.address, count(*) addressCount from Output o left join `Key` k on (o.key_id = k.id) group by k.address order by addressCount desc limit 10;

       Addresses with most sends:
       select k.address, count(*) addressCount from Input i left join `Key` k on (i.key_id = k.id) where k.address is not NULL group by k.address order by addressCount desc limit 10;

     */

    public function import()
    {/*{{{*/
        if (extension_loaded('xhprof')) {
            // xhprof slows things down
            xhprof_disable();
        }

        if (!extension_loaded('gmp')) {
            throw new Exception('The blockchain importer requires the PHP GMP module to be installed and enabled');
        }

        echo "
Todo:

* detect if PHP is 64-bit, if not, check if GMP is installed, otherwise us BCMath
  - ACTUALLY, just move to PHPSECLIB: http://phpseclib.sourceforge.net/math/intro.html 
* move RPC calls into separate class
* move bitcoin specific utils into separate class
* move conversion from satoshis to floats into view model
* fix trailing decimal point on whole numbers in block and blockchain views

Maybe:

* move entities to model binder?
";

        sleep(1);

        // this can take a long time
        set_time_limit(0);

        $client = new \Zend\Json\Server\Client($this->bitcoindServerUrl);
        $client->getHttpClient()->setOptions(array('timeout' => 30));
        $blockcount = $client->call('getblockcount');

        // Get the last block in the DB
        $query = $this->objectManager->createQuery('SELECT b FROM Blockchain\Entity\Block b WHERE b.id = (SELECT MAX(bm.id) FROM Blockchain\Entity\Block bm)');
        $result = $query->getResult();
        if (count($result) == 1) {
            $blockEntity = $result[0];
            $blockId = $blockEntity->getId();
            $blockhash = $blockEntity->getBlockhash();
            $blockNumber = $blockEntity->getBlockNumber();

            $block = $this->getBlockFromServer($blockhash);

            // remove last block and all associated transactions in case it wasn't loaded full or there was no "nextblockhash"
            $connection = $this->objectManager->getConnection();
            if ($connection->getDatabasePlatform()->getName() == 'mysql') {
                // The input and output tables have cyclical foreign keys, so rows can't be deleted
                $connection->query('SET FOREIGN_KEY_CHECKS=0'); 
            }
            $this->objectManager->remove($blockEntity);
            $this->objectManager->flush();
            if ($connection->getDatabasePlatform()->getName() == 'mysql') {
                $connection->query('SET FOREIGN_KEY_CHECKS=1'); 
            }
            
            $coinbaseExp = floor($blockNumber / 210000);
            $gmp_coinbaseValue = gmp_div_q(gmp_init("5000000000"), gmp_pow(gmp_init("2"), $coinbaseExp));
        } else {
            $blockNumber = 0;
            $blockhash = $client->call('getblockhash', array($blockNumber));
            $gmp_coinbaseValue = gmp_init("5000000000");
        }

        $batchSize = 25; 

        $count = 0;
        // Start importing 
        while($blockhash) {
            if ($blockNumber % 210000 == 0) {
                // only calculate this when necessary instead of every loop
                $coinbaseExp = floor($blockNumber / 210000);
                $gmp_coinbaseValue = gmp_div_q(gmp_init("5000000000"), gmp_pow(gmp_init("2"), $coinbaseExp));
            }
            
            echo "\nBlock $blockNumber, Transactions: ";

            $gmp_totalBlockValue = gmp_init("0");

            $block = $this->getBlockFromServer($blockhash);

            $blockEntity = new \Blockchain\Entity\Block();
            $blockEntity->setBlockNumber($blockNumber);
            $blockEntity->setBlockhash($block['hash']);
            $blockEntity->setSize($block['size']);
            $blockEntity->setHeight($block['height']);
            $blockEntity->setVersion($block['version']);
            $blockEntity->setMerkleroot($block['merkleroot']);
            $blockEntity->setTime(new \DateTime('@'.$block['time']));
            $blockEntity->setNonce($block['nonce']);
            $blockEntity->setBits($block['bits']);
            $blockEntity->setDifficulty($block['difficulty']);
            if (isset($block['nextblockhash'])) {
                $blockEntity->setNextblockhash($block['nextblockhash']);
            }
            if (isset($block['previousblockhash'])) {
                $blockEntity->setPreviousblockhash($block['previousblockhash']);
            }
            $this->objectManager->persist($blockEntity);
            $count++;

            $gmp_offeredFees = gmp_init("0");
            $gmp_takenFees = gmp_init("0");

            // First block is unique
            if ($blockNumber > 0) {
                $seenTxids = array();
                $seenAddresses = array();
                $txCount = 0;
                foreach ($block['tx'] as $txid) {
                    $txCount++;
                    if ($txCount > 1) { echo ', '; }
                    echo $txCount;
                    // the JSON RPC client appears to have a memory leak, so isolate it inside a function
                    $transaction = $this->getRawTransactionFromServer($txid);
                    
                    if (!$transaction) {
                        die('failure retrieving transaction');
                    }

                    if ($blockNumber == 91842 && $transaction['txid'] == 'd5d27987d2a3dfc724e359870c6644b40e497bdc0589a033220fe15429d88599') {
                        // Special case where a transaction was duplicated in block 91812 and 91842 due to a bug in a mining client
                        // ignore the second instance
                        continue;
                    }

                    if ($blockNumber == 91880 && $transaction['txid'] == 'e3bf3d07d4b0375638d5f1db5255fe07ba2c4cb067cd81b84ee974b6585fb468') {
                        // Special case where a transaction was duplicated in block 91722 and 91880 due to a bug in a mining client
                        // ignore the second instance
                        continue;
                    }

                    $transactionEntity = new \Blockchain\Entity\Transaction();
                    $transactionEntity->setTxid($transaction['txid']);
                    $transactionEntity->setBlockhash($blockhash);
                    $transactionEntity->setBlock($blockEntity);
                    $transactionEntity->setVersion($transaction['version']);
                    $transactionEntity->setLocktime($transaction['locktime']);
                    $transactionEntity->setSize($transaction['size']);
                    $this->objectManager->persist($transactionEntity);
                    $count++;

                    $gmp_totalInputValue = gmp_init("0");
                    foreach ($transaction['vin'] as $input) {
                        $inputEntity = new \Blockchain\Entity\Input();
                        $inputEntity->setTxid($txid);
                        $inputEntity->setSequence($input['sequence']);
                        if (isset($input['coinbase'])) {
                            $inputEntity->setCoinbase($input['coinbase']);
                            $inputEntity->setValue(gmp_strval($gmp_coinbaseValue));
                            $gmp_totalInputValue = gmp_add($gmp_totalInputValue, $gmp_coinbaseValue);
                        } else {
                            if (isset($seenTxids[$input['txid']])) {
                                // input of one transaction is referencing the output of another transaction in the same block.  flush writes.
                                $this->objectManager->flush();
                                // clear objects to free memory
                                $this->objectManager->clear();
                                // reload associated entities
                                $blockEntity = $this->objectManager->getRepository('Blockchain\Entity\Block')->findOneBy(array('blockhash' => $blockhash));
                                $transactionEntity = $this->objectManager->getRepository('Blockchain\Entity\Transaction')->findOneBy(array('txid' => $txid));
                            }
                            $inputEntity->setRedeemedTxid($input['txid']);
                            $inputEntity->setScriptSigAsm($input['scriptSig']['asm']);
                            $inputEntity->setScriptSigHex($input['scriptSig']['hex']);
                            $inputEntity->setVout($input['vout']);
                            $redeemedOutputEntity = $this->objectManager->getRepository('Blockchain\Entity\Output')->findOneBy(array('txid' => $input['txid'], 'n' => $input['vout']));
                            if (!$redeemedOutputEntity) {
                                die('could not find output');
                            }

                            $pubkey = null;
                            $hash160 = null;
                            $address = null;
                            $keyEntity = null;
                            if (preg_match('/(.+) (.+)/', $input['scriptSig']['asm'], $matches)) {

                                // Standard Transaction to Bitcoin address (pay-to-pubkey-hash)
                                // scriptPubKey: OP_DUP OP_HASH160 <pubKeyHash> OP_EQUALVERIFY OP_CHECKSIG
                                // scriptSig: <sig> <pubKey>
                                
                                $signature = $matches[1];
                                $pubkey = $matches[2];
                                $hash160 = self::pubkeyToHash160($pubkey);
                                $address = self::hash160ToAddress($hash160);
                            } else if (preg_match('/([\S]+)/', $input['scriptSig']['asm'])) {

                                // Standard Generation Transaction (pay-to-pubkey)
                                // scriptPubKey: <pubKey> OP_CHECKSIG
                                // scriptSig: <sig>

                                $redeemedOutputKeyEntity = $redeemedOutputEntity->getKey();
                                if ($redeemedOutputKeyEntity) {
                                    $pubkey = $redeemedOutputKeyEntity->getPubkey();
                                    $hash160 = $redeemedOutputKeyEntity->getHash160();
                                    $address = $redeemedOutputKeyEntity->getAddress();
                                } else {
                                    die("Standard Generation without key\n");
                                }
                            } else {
                                die("strange scriptSig: ".$input['scriptSig']['asm']."\n");
                            }

                            $keyEntity = $this->objectManager->getRepository('Blockchain\Entity\Key')->findOneBy(array('address' => $address));
                            if (!$keyEntity) {
                                if (!isset($seenAddresses[$address])) {
                                    $keyEntity = new \Blockchain\Entity\Key(); 
                                    $keyEntity->setPubkey($pubkey);
                                    $keyEntity->setHash160($hash160);
                                    $keyEntity->setAddress($address);
                                    $keyEntity->setFirstblockhash($blockhash);
                                    $keyEntity->setFirstblock($blockEntity);
                                    $seenAddresses[$address] = array('pubkey' => $pubkey);
                                } else {
                                    $this->objectManager->flush();
                                    $keyEntity = $this->objectManager->getRepository('Blockchain\Entity\Key')->findOneBy(array('address' => $address));
                                }
                            }

                            if (!$keyEntity) {
                                die("problem finding input key: $address\n");
                            }

                            if ($pubkey && !$keyEntity->getPubkey()) {
                                $keyEntity->setPubkey($pubkey);
                            }
                            $this->objectManager->persist($keyEntity);

                            $inputEntity->setKey($keyEntity);
                            $inputEntity->setHash160($hash160);
                            $inputEntity->setAddress($address);

                            $gmp_inputValue = gmp_init($redeemedOutputEntity->getValue());
                            $inputEntity->setValue(gmp_strval($gmp_inputValue));
                            $inputEntity->setAddress($redeemedOutputEntity->getAddress());
                            $inputEntity->setRedeemedOutput($redeemedOutputEntity);
                            $redeemedOutputEntity->setRedeemingInput($inputEntity);
                            $this->objectManager->persist($redeemedOutputEntity);
                            $gmp_totalInputValue = gmp_add($gmp_totalInputValue, gmp_init($inputEntity->getValue()));
                            // echo 'txid: '.$txid.', input txid: '.$input['txid'].', vout: '.$input['vout'].", val: ".gmp_strval($gmp_inputValue)."\n";
                            // Need to figure out how to extract hash160, if it is of any value...
                            // $inputEntity->setHash160();
                        }
                        $inputEntity->setTransaction($transactionEntity);
                        $this->objectManager->persist($inputEntity);
                        $count++;
                    }

                    $gmp_totalBlockValue = gmp_add($gmp_totalBlockValue, $gmp_totalInputValue);

                    $gmp_totalOutputValue = gmp_init("0");
                    foreach ($transaction['vout'] as $output) {
                        $outputEntity = new \Blockchain\Entity\Output();
                        $outputEntity->setTxid($txid);
                        $outputEntity->setN($output['n']);
                        $gmp_outputValue = self::floatBTCToGmpSatoshis($output['value']);
                        $outputEntity->setValue(gmp_strval($gmp_outputValue));
                        $gmp_totalOutputValue = gmp_add($gmp_totalOutputValue, $gmp_outputValue);
                        $outputEntity->setScriptPubKeyAsm($output['scriptPubKey']['asm']);
                        $outputEntity->setScriptPubKeyHex($output['scriptPubKey']['hex']);
                        $pubkey = null;
                        $hash160 = null;
                        $address = null;
                        if (preg_match('/OP_DUP OP_HASH160 ([\S]+) OP_EQUALVERIFY OP_CHECKSIG/', $output['scriptPubKey']['asm'], $matches)) {
                            $hash160 = $matches[1];
                            $address = self::hash160ToAddress($hash160);
                        } else if (preg_match('/^([\S]+) OP_CHECKSIG$/', $output['scriptPubKey']['asm'], $matches)) {
                            $pubkey = $matches[1];
                            $hash160 = self::pubkeyToHash160($pubkey);
                            $address = self::hash160ToAddress($hash160);
                        }
                        $keyEntity = null;
                        if ($address) {
                            $keyEntity = $this->objectManager->getRepository('Blockchain\Entity\Key')->findOneBy(array('address' => $address));
                            if (!$keyEntity) {
                                if (!isset($seenAddresses[$address])) {
                                    $keyEntity = new \Blockchain\Entity\Key(); 
                                    $keyEntity->setPubkey($pubkey);
                                    $keyEntity->setHash160($hash160);
                                    $keyEntity->setAddress($address);
                                    $keyEntity->setFirstblockhash($blockhash);
                                    $keyEntity->setFirstblock($blockEntity);
                                    $seenAddresses[$address] = array('pubkey' => $pubkey);
                                } else {
                                    $this->objectManager->flush();
                                    $keyEntity = $this->objectManager->getRepository('Blockchain\Entity\Key')->findOneBy(array('address' => $address));
                                }
                            }
                            if ($keyEntity) {
                                if ($pubkey && !($keyEntity->getPubkey())) {
                                    $keyEntity->setPubkey($pubkey);
                                }

                                $this->objectManager->persist($keyEntity);
                                $outputEntity->setKey($keyEntity);
                            }
                            if (!$keyEntity && $pubkey && !$seenAddresses[$address]['pubkey'])
                            {
                                die ("Situation: address seen multiple times in transaction, and pubkey available\n");
                            }
                        }
                        if ($address && !$keyEntity) {
                            die("Output key entity not generated: $address\n");
                        }
                        /*
                        if ($txid == '00e45be5b605fdb2106afa4cef5992ee6d4e3724de5dc8b13e729a3fc3ad4b94') {
                            if ($address == '1AbHNFdKJeVL8FRZyRZoiTzG9VCmzLrtvm') {
                                echo $outputEntity->getKey()->getAddress()."\n";
                                die();
                            } else {
                                echo "$address\n";
                            }
                        }
                        */
                        $outputEntity->setHash160($hash160);
                        if (isset($output['scriptPubKey']['reqSigs'])) {
                            $outputEntity->setReqSigs($output['scriptPubKey']['reqSigs']);
                        }
                        $outputEntity->setType($output['scriptPubKey']['type']);
                        if (count($output['scriptPubKey']['addresses']) > 1) {
                            echo "output with multiple addresses found:\n";
                            echo print_r($output, true);
                            die();
                        }
                        if (isset($output['scriptPubKey']['addresses'][0]) && $address != $output['scriptPubKey']['addresses'][0]) {
                            echo  $output['scriptPubKey']['asm']."\n";
                            die ("inconsistent output addresses\n    parsed: $address\n    given: {$output['scriptPubKey']['addresses'][0]}\n");
                        }
                        $outputEntity->setAddress($output['scriptPubKey']['addresses'][0]);
                        $outputEntity->setHash160($hash160);
                        $outputEntity->setTransaction($transactionEntity);
                        $this->objectManager->persist($outputEntity);
                        $count++;
                    }

                    if (isset($input['coinbase'])) {
                        $gmp_takenFees = gmp_sub($gmp_totalOutputValue, $gmp_coinbaseValue);
                    }

                    if (!isset($input['coinbase'])) {
                        $gmp_fee = gmp_sub($gmp_totalInputValue, $gmp_totalOutputValue);
                        $gmp_offeredFees = gmp_add($gmp_offeredFees, $gmp_fee);
                    } else {
                        $gmp_fee = gmp_init("0");
                    }
                    $transactionEntity->setFee(gmp_strval($gmp_fee));
                    $transactionEntity->setTotalIn(gmp_strval($gmp_totalInputValue));
                    $transactionEntity->setTotalOut(gmp_strval($gmp_totalOutputValue));
                    $this->objectManager->persist($transactionEntity);
                    $count++;

                    $seenTxids[$txid] = true;

                    if ($count % $batchSize == 0) {
                        $this->objectManager->flush();
                        $this->objectManager->clear();
                        $blockEntity = $this->objectManager->getRepository('Blockchain\Entity\Block')->findOneBy(array('blockhash' => $blockhash));
                    }
                }
            }

            if (gmp_cmp($gmp_offeredFees, $gmp_takenFees) > 0) {
                echo "WARNING: Possible lost coins. Offered Fees: ".gmp_strval($gmp_offeredFees).", Taken Fees: ".gmp_strval($gmp_takenFees)."\n";
            }
            if (gmp_cmp($gmp_takenFees, $gmp_offeredFees) > 0) {
                die("ERROR.  Impossible transaction: Offered Fees: ".gmp_strval($gmp_offeredFees).", Taken Fees: ".gmp_strval($gmp_takenFees)."\n");
            }
            
            $blockEntity->setOfferedFees(gmp_strval($gmp_offeredFees));
            $blockEntity->setTakenFees(gmp_strval($gmp_takenFees));
            $blockEntity->setTotalvalue(gmp_strval($gmp_totalBlockValue));
            $gmp_lostValue = gmp_sub($gmp_offeredFees, $gmp_takenFees);
            $blockEntity->setLostvalue(gmp_strval($gmp_lostValue));
            $this->objectManager->persist($blockEntity);
            $count++;

            if (isset($block['nextblockhash'])) {
                $nextblockhash = $block['nextblockhash'];
            } else {
                $nextblockhash = null;
            }

            $this->objectManager->flush();
            $this->objectManager->clear();

            $blockhash = $nextblockhash;
            $blockNumber++;
        }
    }/*}}}*/

    public function getLargestBlock()
    {/*{{{*/
        $query = $this->objectManager->createQuery('
            SELECT b, MAX(b.size) as maxSize
            FROM Blockchain\Entity\Block b
            GROUP BY b.id
            ORDER BY maxSize DESC')
           ->setMaxResults(1);

        $result = $query->getResult();
        return $result[0][0]->getBlockNumber();
    }/*}}}*/

    public function getPriciestBlock()
    {/*{{{*/
        $query = $this->objectManager->createQuery('
            SELECT b, MAX(b.totalvalue) as maxValue
            FROM Blockchain\Entity\Block b
            GROUP BY b.id
            ORDER BY maxValue DESC')
           ->setMaxResults(1);

        $result = $query->getResult();
        return $result[0][0]->getBlockNumber();
    }/*}}}*/

    public function getLargestTransaction()
    {/*{{{*/
        $query = $this->objectManager->createQuery('
            SELECT t, MAX(t.size) as maxSize
            FROM Blockchain\Entity\Transaction t
            GROUP BY t.id
            ORDER BY maxSize DESC')
           ->setMaxResults(1);

        $result = $query->getResult();
        return $result[0][0]->getTxid();
    }/*}}}*/

    public function getPriciestTransaction()
    {/*{{{*/
        $query = $this->objectManager->createQuery('
            SELECT t, MAX(t.totalIn) as maxValue
            FROM Blockchain\Entity\Transaction t
            GROUP BY t.id
            ORDER BY maxValue DESC')
           ->setMaxResults(1);

        $result = $query->getResult();
        return $result[0][0]->getTxid();
    }/*}}}*/

    public function getBlocks($startBlockNumber = null, $endBlockNumber = null)
    {/*{{{*/
        $blockList = array();
        $whereSet = false;
        $qb = $this->objectManager->createQueryBuilder();
        $qb->select('b')
           ->from('Blockchain\Entity\Block', 'b')
           ->orderBy('b.id', 'DESC')
           ->setMaxResults(20);

        if ($startBlockNumber) {
            $qb->where($qb->expr()->gte('b.blockNumber', $startBlockNumber));
            $whereSet = true;
        }

        if ($endBlockNumber) {
            $expr = $qb->expr()->lte('b.blockNumber', $endBlockNumber);
            if ($whereSet) {
                $qb->andWhere($expr);
            } else {
                $qb->where($expr);
                $whereSet = true;
            }
        }

        $query = $qb->getQuery();
        $doctrinePaginator = new \Doctrine\ORM\Tools\Pagination\Paginator($query);
        // the following line prevents doctrine from loading the entire table into a temp table
        $doctrinePaginator->setUseOutputWalkers(false);
        $doctrinePaginatorAdapter = new \DoctrineORMModule\Paginator\Adapter\DoctrinePaginator($doctrinePaginator);
        $paginator = new \Zend\Paginator\Paginator($doctrinePaginatorAdapter);
        $paginator->setItemCountPerPage(20);
        $paginator->setPageRange(14);

        /*
        if (count($result)) {
            foreach ($result as $blockEntity) {
                $blockList[] = array(
                    'blocknumber' => $blockEntity->getBlockNumber(),
                    'blockhash' => $blockEntity->getBlockhash(),
                    'blockhashTruncated' => substr($blockEntity->getBlockhash(), 0, 25).'...',
                    'time' => $blockEntity->getTime()->format('Y-m-d H:i:s'),
                    'transactionCount' => $blockEntity->getTransactions()->count(),
                    'totalBTC' => self::gmpSatoshisToFloatBTC(gmp_init($blockEntity->getTotalvalue())),
                    'offeredFees' => self::gmpSatoshisToFloatBTC(gmp_init($blockEntity->getOfferedFees())),
                    'takenFees' => self::gmpSatoshisToFloatBTC(gmp_init($blockEntity->getTakenFees())),
                    'size' => $blockEntity->getSize()
                );
            }
        }

        return $blockList;
        */
        return $paginator;
    }/*}}}*/

    public function search($phrase)
    {/*{{{*/
        $phrase = trim($phrase);

        if (preg_match('/^[0-9]+$/', $phrase)) {
            // numbers only: block number
            $blockEntity = $this->objectManager->getRepository('Blockchain\Entity\Block')->findOneBy(array('blockNumber' => $phrase));
            if (!$blockEntity) {
                $retVal = array(
                    'success' => false,
                    'message' => 'block number not found',
                );
            } else {
                $retVal = array(
                    'success' => true,
                    'type' => 'blocknumber',
                    'id' => $phrase
                );
            }
        } else if (self::validAddress($phrase)) {
            // address
            $keyEntity = $this->objectManager->getRepository('Blockchain\Entity\Key')->findOneBy(array('address' => $phrase));
            if (!$keyEntity) {
                $retVal = array(
                    'success' => false,
                    'message' => 'address not found',
                );
            } else {
                $retVal = array(
                    'success' => true,
                    'type' => 'address',
                    'id' => $phrase
                );
            }
        } else {
            // then try block hash, tx hash, hash160, then pubkey
            $retVal['success'] = true;
            $retVal['id'] = $phrase;
            $blockEntity = $this->objectManager->getRepository('Blockchain\Entity\Block')->findOneBy(array('blockhash' => $phrase));
            if (!$blockEntity) {
                $transactionEntity = $this->objectManager->getRepository('Blockchain\Entity\Transaction')->findOneBy(array('txid' => $phrase));
                if (!$transactionEntity) {
                    $keyEntity = $this->objectManager->getRepository('Blockchain\Entity\Key')->findOneBy(array('hash160' => $phrase));
                    if (!$keyEntity) {
                        $keyEntity = $this->objectManager->getRepository('Blockchain\Entity\Key')->findOneBy(array('pubkey' => $phrase));
                        if (!$keyEntity) {
                            $retVal = array(
                                'success' => false,
                                'message' => 'no matching records',
                            );
                        } else {
                            $retVal['id'] = $keyEntity->getAddress();
                            $retVal['type'] = 'address';
                        }
                    } else {
                        $retVal['id'] = $keyEntity->getAddress();
                        $retVal['type'] = 'address';
                    }
                } else {
                    $retVal['type'] = 'txid';
                }
            } else {
                $retVal['type'] = 'blockhash';
            }
        }

        return $retVal;
    }/*}}}*/

    public function getBlockByHash($blockhash)
    {/*{{{*/
        $blockEntity = $this->objectManager->getRepository('Blockchain\Entity\Block')->findOneBy(array('blockhash' => $blockhash));
        return $this->getBlockData($blockEntity);
    }/*}}}*/

    public function getBlockByNumber($blocknumber)
    {/*{{{*/
        $blockEntity = $this->objectManager->getRepository('Blockchain\Entity\Block')->findOneBy(array('blockNumber' => $blocknumber));
        return $this->getBlockData($blockEntity);
    }/*}}}*/

    protected function getBlockData($blockEntity)
    {/*{{{*/
        $transactions = array();
        foreach ($blockEntity->getTransactions() as $transactionEntity) {
            $transaction = array(
                'txid' => $transactionEntity->getTxid(),
                'txidTruncated' => substr($transactionEntity->getTxid(), 0, 25).'...',
                'fee' => self::gmpSatoshisToFloatBTC(gmp_init($transactionEntity->getFee())),
                'size' => $transactionEntity->getSize(),
                'from' => array(),
                'to' => array()
            );
            foreach ($transactionEntity->getInputs() as $inputEntity) {
                if ($inputEntity->getCoinbase()) {
                    $amount = self::gmpSatoshisToFloatBTC(gmp_init($inputEntity->getValue()));
                    $takenFees = self::gmpSatoshisToFloatBTC(gmp_init($blockEntity->getTakenFees()));
                    $input = array(
                        'isCoinbase' => true,
                        'type' => 'coinbase',
                        'amount' => self::gmpSatoshisToFloatBTC(gmp_init($inputEntity->getValue())),
                        'takenFees' => self::gmpSatoshisToFloatBTC(gmp_init($blockEntity->getTakenFees())),
                        'hasTakenFees' => $takenFees > 0,
                        'hasLostFees' => $takenFees < 0,
                    );
                } else {
                    $input = array(
                        'isCoinbase' => false,
                        'type' => 'pubkey',
                        'amount' => self::gmpSatoshisToFloatBTC(gmp_init($inputEntity->getValue())),
                        'address' => $inputEntity->getAddress()
                    );
                }
                $transaction['from'][] = $input;
            }
            foreach ($transactionEntity->getOutputs() as $outputEntity) {
                $output = array(
                    'amount' => self::gmpSatoshisToFloatBTC(gmp_init($outputEntity->getValue())),
                    'address' => $outputEntity->getAddress()
                );
                $transaction['to'][] = $output;
            }

            $transactions[] = $transaction;
        }

        $blockData = array(
            'blocknumber' => $blockEntity->getBlockNumber(),
            'blockhash' => $blockEntity->getBlockhash(),
            'previousblockhash' => $blockEntity->getPreviousblockhash(),
            'nextblockhash' => $blockEntity->getNextblockhash(),
            'difficulty' => $blockEntity->getDifficulty(),
            'bits' => $blockEntity->getBits(),
            'nonce' => $blockEntity->getNonce(),
            'merkleroot' => $blockEntity->getMerkleroot(),
            'time' => $blockEntity->getTime()->format('Y-m-d H:i:s'),
            'transactionCount' => $blockEntity->getTransactions()->count(),
            'totalBTC' => $this->gmpSatoshisToFloatBTC(gmp_init($blockEntity->getTotalvalue())),
            'lostBTC' => self::gmpSatoshisToFloatBTC(gmp_init($blockEntity->getLostValue())),
            'offeredFees' => self::gmpSatoshisToFloatBTC(gmp_init($blockEntity->getOfferedFees())),
            'takenFees' => self::gmpSatoshisToFloatBTC(gmp_init($blockEntity->getTakenFees())),
            'size' => $blockEntity->getSize(),
            'transactions' => $transactions
        );

        return $blockData;
    }/*}}}*/

    public function getTransaction($txid)
    {/*{{{*/
        $transactionEntity = $this->objectManager->getRepository('Blockchain\Entity\Transaction')->findOneBy(array('txid' => $txid));
        if ($transactionEntity) {
            return $this->getTransactionData($transactionEntity);
        }
        return null;
    }/*}}}*/

    protected function getTransactionData($transactionEntity)
    {/*{{{*/
        $blockEntity = $transactionEntity->getBlock();

        $inputs = array();
        $isCoinbase = false;
        foreach ($transactionEntity->getInputs() as $inputEntity) {
            $input = array(
                'isCoinbase' => false,
                'amount' => self::gmpSatoshisToFloatBTC(gmp_init($inputEntity->getValue())),
                'scriptSig' => $inputEntity->getScriptSigAsm()
            );
            $input['amountFormatted'] = $input['amount'];
            if ($inputEntity->getCoinbase()) {
                $isCoinbase = true;
                $input['isCoinbase'] = true;
                $input['type'] = 'Generation';
                $input['scriptSig'] = $inputEntity->getCoinbase();
                if ($blockEntity->getTakenFees()) {
                    $input['amountFormatted'] = $input['amount'] . ' + ' . self::gmpSatoshisToFloatBTC(gmp_init($blockEntity->getTakenFees())) . ' fees';
                }
            } else {
                $input = array_merge($input, array(
                    'previousTxid' => $inputEntity->getRedeemedTxid(),
                    'previousTxidTruncated' => substr($inputEntity->getRedeemedTxid(), 0, 25).'...',
                    'vout' => $inputEntity->getVout(),
                    'fromAddress' => $inputEntity->getAddress(),
                    'type' => 'pubkey',
                ));
            }

            $inputs[] = $input;
        }

        $outputs = array();
        foreach ($transactionEntity->getOutputs() as $outputEntity) {
            $redeemingInput = $outputEntity->getRedeemingInput();
            if ($redeemingInput) {
                $redeemedAt = $redeemingInput->getTxid();
                $redeemedAtTruncated = substr($redeemedAt, 0, 15).'...';
            } else {
                $redeemedAt = null;
                $redeemedAtTruncated = null;
            }
            $output = array(
                'index' => $outputEntity->getN(),
                'redeemedAt' => $redeemedAt,
                'redeemedAtTruncated' => $redeemedAtTruncated,
                'amount' => self::gmpSatoshisToFloatBTC(gmp_init($outputEntity->getValue())),
                'address' => $outputEntity->getAddress(),
                'type' => $outputEntity->getType(),
                'scriptPubKey' => $outputEntity->getScriptPubKeyAsm(),
            );
            $outputs[] = $output;
        }

        $transactionData = array(
            'txid' => $transactionEntity->getTxid(),
            'blocknumber' => $blockEntity->getBlocknumber(),
            'blocktime' => $blockEntity->getTime()->format('Y-m-d H:i:s'),
            'inputCount' => $transactionEntity->getInputs()->count(),
            'totalIn' => self::gmpSatoshisToFloatBTC(gmp_init($transactionEntity->getTotalIn())),
            'outputCount' => $transactionEntity->getOutputs()->count(),
            'totalOut' => self::gmpSatoshisToFloatBTC(gmp_init($transactionEntity->getTotalOut())),
            'size' => $transactionEntity->getSize(),
            'inputs' => $inputs,
            'outputs' => $outputs,
        );

        if ($isCoinbase) {
            $fee = self::gmpSatoshisToFloatBTC(gmp_init($blockEntity->getTakenFees()));
            $transactionData['totalIn'] += $fee;
            $transactionData['fee'] = - $fee;
        } else {
            $transactionData['fee'] = self::gmpSatoshisToFloatBTC(gmp_init($transactionEntity->getFee()));
        }

        return $transactionData;
    }/*}}}*/

    public function getAddressActivity($address)
    {/*{{{*/
        $keyEntity = $this->objectManager->getRepository('Blockchain\Entity\Key')->findOneBy(array('address' => $address));
        if (!$keyEntity) {
            return false;
        } else {
            return $this->getAddressData($keyEntity);
        }
    }/*}}}*/

    protected function getAddressData($keyEntity)
    {/*{{{*/
        $query = $this->objectManager->createQuery('
                SELECT o FROM Blockchain\Entity\Output o
                LEFT JOIN o.key k
                WHERE k.address = :address')
            ->setParameter('address', $keyEntity->getAddress());

        $results = $query->getResult();
        $transactions = array();

        $receivedCount = 0;
        foreach ($results as $outputEntity) {
            $transactionEntity = $outputEntity->getTransaction();
            $blockEntity = $transactionEntity->getBlock();
            $output = array(
                'id' => $outputEntity->getId(),
                'txid' => $outputEntity->getTxid(),
                'txidTruncated' => substr($outputEntity->getTxid(), 0, 25).'...',
                'txType' => 'received',
                'blocknumber' => $blockEntity->getBlocknumber(),
                'blocktime' => $blockEntity->getTime()->getTimestamp(),
                'blocktimeFormatted' => $blockEntity->getTime()->format('Y-m-d H:i:s'),
                'amountSatoshis' => $outputEntity->getValue(),
                'amount' => self::gmpSatoshisToFloatBTC(gmp_init($outputEntity->getValue())),
                'type' => ($outputEntity->getType() == 'pubkeyhash' ? 'Address' : ucfirst($outputEntity->getType())),
                'fromto' => array()
            );
            foreach ($transactionEntity->getInputs() as $inputEntity) {
                if ($inputEntity->getCoinbase()) {
                    $output['fromto'][] = array(
                        'isGeneration' => true,
                    );
                } else {
                    $inputKeyEntity = $inputEntity->getKey();
                    $output['fromto'][] = array(
                        'isGeneration' => false,
                        'address' => $inputKeyEntity ? $inputKeyEntity->getAddress() : "f'd up",
                    );
                }
            }

            $transactions[] = $output;
            $receivedCount++;
        }

        $query = $this->objectManager->createQuery('
                SELECT i FROM Blockchain\Entity\Input i
                LEFT JOIN i.key k
                WHERE k.address = :address')
            ->setParameter('address', $keyEntity->getAddress());

        $results = $query->getResult();

        $sentCount = 0;
        foreach ($results as $inputEntity) {
            $transactionEntity = $inputEntity->getTransaction();
            $blockEntity = $transactionEntity->getBlock();
            $input = array(
                'id' => $inputEntity->getId(),
                'txid' => $inputEntity->getTxid(),
                'txidTruncated' => substr($inputEntity->getTxid(), 0, 25).'...',
                'txType' => 'sent',
                'blocknumber' => $blockEntity->getBlocknumber(),
                'blocktime' => $blockEntity->getTime()->getTimestamp(),
                'blocktimeFormatted' => $blockEntity->getTime()->format('Y-m-d H:i:s'),
                'amountSatoshis' => $inputEntity->getValue(),
                'amount' => self::gmpSatoshisToFloatBTC(gmp_init($inputEntity->getValue())),
                'type' => null,
                'fromto' => array()
            );
            foreach ($transactionEntity->getOutputs() as $outputEntity) {
                $input['fromto'][] = array(
                    'isGeneration' => false,
                    'address' => $outputEntity->getKey()->getAddress(),
                );

                if ($outputEntity->getType() == 'pubkeyhash')  {
                    $type = 'Address';
                } else {
                    $type = ucfirst($outputEntity->getType());
                }
                if (!$input['type']) {
                    $input['type'] = $type;
                } else if ($input['type'] != $type) {
                    $input['type'] = 'Mixed';
                }
            }
            $transactions[] = $input;
            $sentCount++;
        }

        usort($transactions, function($a, $b) {
            if ($a['blocktime'] == $b['blocktime']) {
                if ($a['txType'] == $b['txType']) {
                    return ($a['id'] - $b['id']);
                }
                
                switch ($a['txType']) {
                    case 'received':
                        return -1;
                        break;
                    case 'sent':
                        return 1;
                        break;
                    default:
                        return 0;
                    break;
                }
            }

            return ($a['blocktime'] - $b['blocktime']);
        });

        $balance = gmp_init('0');
        $receivedTotalValue = gmp_init('0');
        $sentTotalValue = gmp_init('0');
        foreach($transactions as &$transaction) {
            $amount = gmp_init($transaction['amountSatoshis']);
            switch($transaction['txType']) {
                case 'received':
                    $balance = gmp_add($balance, $amount);
                    $receivedTotalValue = gmp_add($receivedTotalValue, $amount);
                    break;
                case 'sent':
                    $balance = gmp_sub($balance, $amount);
                    $sentTotalValue = gmp_add($sentTotalValue, $amount);
                    break;
                default:
                    die($transaction['txType']);
                    break;
            }
            $transaction['balance'] = self::gmpSatoshisToFloatBTC($balance);
        }

        $addressData = array(
            'address' => $keyEntity->getAddress(),
            'firstseen' => $keyEntity->getFirstblock()->getTime()->format('Y-m-d H:i:s'),
            'receivedTransactions' => $receivedCount,
            'receivedBTC' => self::gmpSatoshisToFloatBTC($receivedTotalValue),
            'sentTransactions' => $sentCount,
            'sentBTC' => self::gmpSatoshisToFloatBTC($sentTotalValue),
            'hash160' => $keyEntity->getHash160(),
            'pubkey' => $keyEntity->getPubkey(),
            'transactions' => $transactions,
        );

        return $addressData;
    }/*}}}*/


/* ---------------------------------------------------------------------------------------------
    Bitcoin utility functions
--------------------------------------------------------------------------------------------- */

    // checks if address is valid
    static public function validAddress($address)
    {/*{{{*/
        // must start with 1 or 3
        if (!preg_match('/^[13]/', $address)) {
            return false;
        }

        // must be between 27 and 34 characters in length
        if (strlen($address) < 27 || strlen($address) > 34) {
            return false;
        }

        // also no uppercase letter "O", uppercase letter "I", lowercase letter "l", and the number "0"
        if (preg_match('/[OIl0]/', $address)) {
            return false;
        }

        // checksum must match
        $addressHex = self::base58ToBase256($address);
        $addressBinary = pack('H*', $addressHex);
        $binary = substr($addressBinary, 0, strlen($addressBinary) - 4);
        $addressChecksum = substr($addressBinary, -4);
        $binaryChecksum = hash('sha256', hash('sha256', $binary, true), true);
        if ($addressChecksum != substr($binaryChecksum, 0, 4)) {
            return false;
        }

        return true;
    }/*}}}*/

    // converts BTC values in floating point to Satoshies in GMP (Gnu Multiple Precision)
    static public function floatBTCToGmpSatoshis($value)
    {/*{{{*/
        // convert to string
        $valueString = "$value";
        if (preg_match('/[\.E]/', $valueString)) {
            $valueString = sprintf('%.8F', $value);
            $valueString = rtrim($valueString, '0');
        }

        // get rid of leading zeros.  GMP interprets leading 0's to mean that the number is octal
        $valueString = ltrim($valueString, '0');

        // find out number of digits after decimal
        $digits = strlen(substr(strrchr($valueString, "."), 1));        

        if (!$digits) {
            // integer
            $gmp_multiplier = gmp_init('100000000');
        } else {
            // not an integer
            if ($digits > 8) {
                // no value should have more than 8 digits after the decimal, as 1E-8 is the smallest BTC subdivision (for the moment)
                die("strange float: $float\n");
            }
            // remove decimal
            $valueString = str_replace('.', '', $valueString);

            // get rid of leading zeros.  GMP interprets leading 0's to mean that the number is octal
            $valueString = ltrim($valueString, '0');

            // used to increase order of magitude by 8 minus number of digits after decimal
            $gmp_multiplier = gmp_init('1'.str_repeat('0', 8-$digits));
        }
        // convert to GMP
        $gmp_value = gmp_init($valueString);

        // increase order of magnitude to convert to Satoshis
        $gmp_value = gmp_mul($gmp_value, $gmp_multiplier);
        
        return $gmp_value;
    }/*}}}*/

    // converts Satoshies in GMP (Gnu Multiple Precision) to BTC values in floating point
    static public function gmpSatoshisToFloatBTC($value)
    {/*{{{*/
        $result = gmp_div_qr($value, gmp_init('100000000'));
        $whole = gmp_strval($result[0]);
        $fraction = sprintf('%08s', gmp_strval($result[1]));
        $btc = floatval("$whole.$fraction");
        return $btc;
    }/*}}}*/

    // Converts base16 string to Base10 string
    static public function base16ToBase10($hexString)
    {/*{{{*/
        $hexString = strtoupper($hexString);
        $retVal = '0';
        $length = strlen($hexString);
        for ($i = 0; $i < $length; $i++) {
            $current = (string) strpos(self::$hexChars, $hexString[$i]);
            $retVal = (string) bcmul($retVal, '16', 0);
            $retVal = (string) bcadd($retVal, $current, 0);
        }
        return $retVal;
    }/*}}}*/

    static public function base10ToBase16($decString)
    {/*{{{*/
        $retVal = '';
        while (bccomp($decString, 0) == 1) {
            $dv = (string) bcdiv($decString, '16', 0);
            $rem = (integer) bcmod($decString, '16');
            $decString = $dv;
            $retVal = $retVal . self::$hexChars[$rem];
        }
        return strrev($retVal);
    }/*}}}*/

    // Converts base16 string encoded binary data into a base58 string
    static public function base256ToBase58($hexString)
    {/*{{{*/
        if (strlen($hexString) % 2 != 0) {
            throw new Exception('hex string must have even number of characters');
        }

        $decString = self::base16ToBase10($hexString);
        $retVal = '';
        while (bccomp($decString, 0) == 1) {
            $dv = (string) bcdiv($decString, '58', 0);
            $rem = (integer) bcmod($decString, '58');
            $decString = $dv;
            $retVal = $retVal . self::$base58chars[$rem];
        }
        $retVal = strrev($retVal);

        // Add leading zeros
        for ($i = 0; $i < strlen($hexString) && substr($hexString, $i, 2) == '00'; $i += 2) {
            $retVal = '1' . $retVal;
        }

        return $retVal;
    }/*}}}*/

    static public function base58ToBase256($encodedString)
    {/*{{{*/
        if (preg_match('/[^1-9A-HJ-NP-Za-km-z]/', $encodedString)) {
            throw new Exception('illegal characters detected');
        }

        $retVal = '0';
        $length = strlen($encodedString);
        for ($i = 0; $i < $length; $i++) {
            $current = (string) strpos(self::$base58chars, $encodedString[$i]);
            $retVal = (string) bcmul($retVal, '58', 0);
            $retVal = (string) bcadd($retVal, $current, 0);
        }

        $retVal = self::base10ToBase16($retVal);

        // Remove leading zeros
        for ($i = 0; $i < $length && $encodedString[$i] == '1'; $i++) {
            $retVal = '00' . $retVal;
        }

        if (strlen($retVal) % 2 != 0) {
            $return = '0' . $retVal;
        }

        return $retVal;
    }/*}}}*/

    // See: https://en.bitcoin.it/w/images/en/9/9b/PubKeyToAddr.png

    // Converts Pubkey into bitcoin hash160 and address
    static public function pubkeyToHash160($pubkey)
    {/*{{{*/
        // prepend 0x04 (why?)
        $string = '04'.$pubkey;
        // only php 5.4 and above support hex2bin
        $binary = pack('H*', $pubkey);
        // hash key
        $hash160 = hash('ripemd160', hash('sha256', $binary, true));

        return $hash160;
    }/*}}}*/

    // Converts hash160 into address
    static public function hash160ToAddress($hash160)
    {/*{{{*/
        // Get value to checksum
        $hash = '00'.$hash160;
        $binary = pack('H*', $hash);
        // get the checksum
        $binaryChecksum = hash('sha256', hash('sha256', $binary, true), true);
        $addressBinary = $binary . substr($binaryChecksum, 0, 4);
        $unpacked = unpack('H*', $addressBinary);
        $addressHex = $unpacked[1];
        $address = self::base256ToBase58($addressHex);

        return $address;
    }/*}}}*/

/* ---------------------------------------------------------------------------------------------
   Bitcoind RPC Functions
--------------------------------------------------------------------------------------------- */

    // the JSON RPC client appears to have a memory leak, so isolate it inside a function
    protected function getBlockFromServer($hash)
    {/*{{{*/
        $client = new \Zend\Json\Server\Client($this->bitcoindServerUrl);
        $client->getHttpClient()->setOptions(array('timeout' => 30));

        $retryCount = 3;
        $block = null;
        do {
            $queryFailed = false;
            try {
                $block = $client->call('getblock', array($hash));
            } catch (Zend\Http\Client\Adapter\Exception $e) {
                echo $e->getMessage();
                $queryFailed = true;
                // sleep 100ms
                usleep(200000);
            }
            $retryCount--;
        } while ((!$block || $queryFailed) && $retryCount);

        return $block;
    }/*}}}*/

    // the JSON RPC client appears to have a memory leak, so isolate it inside a function
    protected function getRawTransactionFromServer($hash)
    {/*{{{*/
        $client = new \Zend\Json\Server\Client($this->bitcoindServerUrl);
        $client->getHttpClient()->setOptions(array('timeout' => 30));

        $retryCount = 3;
        $transaction = null;
        do {
            $queryFailed = false;
            try {
                $rawTransaction = $client->call('getrawtransaction', array($hash));
                $size = strlen($rawTransaction) / 2;
                $transaction = $client->call('decoderawtransaction', array($rawTransaction));
                $transaction['size'] = $size;
            } catch (Zend\Http\Client\Adapter\Exception $e) {
                echo $e->getMessage();
                $queryFailed = true;
                // sleep 100ms
                usleep(200000);
            }
            $retryCount--;
        } while ((!$transaction || $queryFailed) && $retryCount);

        return $transaction;
    }/*}}}*/
}
