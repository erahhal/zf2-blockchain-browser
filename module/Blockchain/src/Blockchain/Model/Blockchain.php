<?php

namespace Blockchain\Model;

class Blockchain
{
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

* This block's taken fees are messed up: http://local-dev.onru.sh/blockchain/block/number/127713
* MOVE from floats to Satoshis (requires a big int library for PHP)
* how to extract public keys
* does block 0 have value and transactions
* how to extract hash160
* move code out of controller

complete:
* is flush called automatically at any point? (it appears that non-flushed changes are saved to DB on ctrl-c
  - it appears that the persist call can flush at any moment
* is flush is atomic (if not need to check if transactions, inputs, and outputs are complete for last block)
  -  there are transactions but they seem like overkill for this use case
  -  Will do a cascade delete of last block in the DB to deal with situations where partial imports occurred

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
            $gmp_circulation = gmp_init($blockEntity->getCirculation());

            $block = $this->getBlockFromServer($blockhash);

            // remove last block and all associated transactions in case it wasn't loaded full or there was no "nextblockhash"
            $this->objectManager->remove($blockEntity);
            $this->objectManager->flush();
            
            $coinbaseExp = floor($blockNumber / 210000);
            $gmp_coinbaseValue = gmp_div_q(gmp_init("5000000000"), gmp_pow(gmp_init("2"), $coinbaseExp));
        } else {
            $blockNumber = 0;
            $blockhash = $client->call('getblockhash', array($blockNumber));
            $gmp_circulation = gmp_init("0");
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
            
            echo "Importing Block: $blockNumber\n";

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
                foreach ($block['tx'] as $txid) {
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
                            $gmp_circulation = gmp_add($gmp_circulation, $gmp_coinbaseValue);
                        } else {
                            if (isset($seenTxids[$input['txid']])) {
                                // input of one transaction is referencing the output of another transaction in the same block.  flush writes.
                                $this->objectManager->flush();
                                $this->objectManager->clear();
                                $blockEntity = $this->objectManager->getRepository('Blockchain\Entity\Block')->findOneBy(array('blockhash' => $blockhash));
                                $transactionEntity = $this->objectManager->getRepository('Blockchain\Entity\Transaction')->findOneBy(array('txid' => $txid));
                            }
                            $inputEntity->setInputTxid($input['txid']);
                            $inputEntity->setScriptSigAsm($input['scriptSig']['asm']);
                            $inputEntity->setScriptSigHex($input['scriptSig']['hex']);
                            $inputEntity->setVout($input['vout']);
                            $previousOutputEntity = $this->objectManager->getRepository('Blockchain\Entity\Output')->findOneBy(array('txid' => $input['txid'], 'n' => $input['vout']));
                            if (!$previousOutputEntity) {
                                die('could not find output');
                            }
                            $gmp_inputValue = gmp_init($previousOutputEntity->getValue());
                            $inputEntity->setValue(gmp_strval($gmp_inputValue));
                            $inputEntity->setAddress($previousOutputEntity->getAddress());
                            $gmp_totalInputValue = gmp_add($gmp_totalInputValue, gmp_init($inputEntity->getValue()));
                            echo 'txid: '.$txid.', input txid: '.$input['txid'].', vout: '.$input['vout'].", val: ".gmp_strval($gmp_inputValue)."\n";
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
                        $gmp_outputValue = $this->floatBTCToGmpSatoshis($output['value']);
                        $outputEntity->setValue(gmp_strval($gmp_outputValue));
                        $gmp_totalOutputValue = gmp_add($gmp_totalOutputValue, $gmp_outputValue);
                        $outputEntity->setScriptPubKeyAsm($output['scriptPubKey']['asm']);
                        $outputEntity->setScriptPubKeyHex($output['scriptPubKey']['hex']);
                        if (isset($output['scriptPubKey']['reqSigs'])) {
                            $outputEntity->setReqSigs($output['scriptPubKey']['reqSigs']);
                        }
                        $outputEntity->setType($output['scriptPubKey']['type']);
                        $outputEntity->setAddress($output['scriptPubKey']['addresses'][0]);
                        // Need to figure out how to extract hash160, if it is of any value...
                        // $outputEntity->setHash160();
                        $outputEntity->setTransaction($transactionEntity);
                        $this->objectManager->persist($outputEntity);
                        $count++;
                    }

                    if (isset($input['coinbase'])) {
                        echo "totalOutputValue: ".gmp_strval($gmp_totalOutputValue).", coinbaseValue: ".gmp_strval($gmp_coinbaseValue)."\n";
                        $gmp_takenFees = gmp_sub($gmp_totalOutputValue, $gmp_coinbaseValue);
                    }

                    if (!isset($input['coinbase'])) {
                        echo "totalInputValue: ".gmp_strval($gmp_totalInputValue).", totalOutputValue: ".gmp_strval($gmp_totalOutputValue)."\n";
                        $gmp_fee = gmp_sub($gmp_totalInputValue, $gmp_totalOutputValue);
                        echo "fee: ".gmp_strval($gmp_fee)."\n";
                        $gmp_offeredFees = gmp_add($gmp_offeredFees, $gmp_fee);
                    } else {
                        $gmp_fee = gmp_init("0");
                    }
                    $transactionEntity->setFee(gmp_strval($gmp_fee));
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
            $blockEntity->setCirculation(gmp_strval($gmp_circulation));
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

    public function getLatestBlocks()
    {/*{{{*/
        $blockList = array();
        $query = $this->objectManager->createQuery('SELECT b FROM Blockchain\Entity\Block b WHERE b.totalvalue IS NOT NULL ORDER BY b.id DESC')->setMaxResults(20);
        $result = $query->getResult();
        if (count($result)) {
            foreach ($result as $blockEntity) {
                $blockList[] = array(
                    'number' => $blockEntity->getBlockNumber(),
                    'blockhash' => $blockEntity->getBlockhash(),
                    'time' => $blockEntity->getTime()->format('Y-m-d H:i:s'),
                    'transactionCount' => $blockEntity->getTransactions()->count(),
                    'totalBTC' => $this->GmpSatoshisToFloatBTC(gmp_init($blockEntity->getTotalvalue())),
                    'offeredFees' => $this->GmpSatoshisToFloatBTC(gmp_init($blockEntity->getOfferedFees())),
                    'takenFees' => $this->GmpSatoshisToFloatBTC(gmp_init($blockEntity->getTakenFees())),
                    'size' => $blockEntity->getSize()
                );
            }
        }

        return $blockList;
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
                'fee' => $this->GmpSatoshisToFloatBTC(gmp_init($transactionEntity->getFee())),
                'size' => $transactionEntity->getSize(),
                'from' => array(),
                'to' => array()
            );
            foreach ($transactionEntity->getInputs() as $inputEntity) {
                if ($inputEntity->getCoinbase()) {
                    $input = array(
                        'type' => 'coinbase',
                        'amount' => $this->GmpSatoshisToFloatBTC(gmp_init($inputEntity->getValue())),
                        'takenFees' => $blockEntity->getTakenFees()
                    );
                } else {
                    $input = array(
                        'type' => 'pubkey',
                        'amount' => $this->GmpSatoshisToFloatBTC(gmp_init($inputEntity->getValue())),
                        'address' => $inputEntity->getAddress()
                    );
                }
                $transaction['from'][] = $input;
            }
            foreach ($transactionEntity->getOutputs() as $outputEntity) {
                $output = array(
                    'amount' => $this->GmpSatoshisToFloatBTC(gmp_init($outputEntity->getValue())),
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
            'totalBTC' => $this->GmpSatoshisToFloatBTC(gmp_init($blockEntity->getTotalvalue())),
            'lostBTC' => $this->GmpSatoshisToFloatBTC(gmp_init($blockEntity->getLostValue())),
            'offeredFees' => $this->GmpSatoshisToFloatBTC(gmp_init($blockEntity->getOfferedFees())),
            'takenFees' => $this->GmpSatoshisToFloatBTC(gmp_init($blockEntity->getTakenFees())),
            'size' => $blockEntity->getSize(),
            'transactions' => $transactions
        );

        return $blockData;
    }/*}}}*/

    // converts BTC values in floating point to Satoshies in PHP GMP (Gnu Multiple Precision)
    public function floatBTCToGmpSatoshis($value)
    {/*{{{*/
        // convert to string
        $valueString = "$value";
        if (preg_match('/[\.E]/', $valueString)) {
            $valueString = sprintf('%.8F', $value);
            $valueString = trim($valueString, '0');
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
        
        echo "[DEBUG] float: $value, GMP: ".gmp_strval($gmp_value)."\n";
        return $gmp_value;
    }/*}}}*/

    public function GmpSatoshisToFloatBTC($value)
    {/*{{{*/
        $result = gmp_div_qr($value, gmp_init('100000000'));
        $btc = floatval(gmp_strval($result[0]).'.'.gmp_strval($result[1]));
        return $btc;
    }/*}}}*/

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
