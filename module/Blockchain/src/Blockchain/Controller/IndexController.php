<?php

namespace Blockchain\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {/*{{{*/
        $objectManager = $this
            ->getServiceLocator()
            ->get('Doctrine\ORM\EntityManager');

        $blockList = array();
        $query = $objectManager->createQuery('SELECT b FROM Blockchain\Entity\Block b WHERE b.totalvalue IS NOT NULL ORDER BY b.id DESC')->setMaxResults(20);
        $result = $query->getResult();
        if (count($result)) {
            foreach ($result as $blockEntity) {
                $blockList[] = array(
                    'number' => $blockEntity->getBlockNumber(),
                    'blockhash' => $blockEntity->getBlockhash(),
                    'time' => $blockEntity->getTime()->format('Y-m-d H:i:s'),
                    'transactionCount' => $blockEntity->getTransactions()->count(),
                    'totalBTC' => $blockEntity->getTotalvalue(),
                    'offeredFees' => $blockEntity->getOfferedFees(),
                    'takenFees' => $blockEntity->getTakenFees(),
                    'size' => $blockEntity->getSize()
                );
            }
        }

        $view = new ViewModel(array(
            'blockList' => $blockList
        ));

        return $view;
    }/*}}}*/

    public function importAction()
    {/*{{{*/
        echo "
Todo:

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
        // this slows things down
        xhprof_disable();

        sleep(1);

        $this->config = $this->getServiceLocator()->get('config');

        // this can take a long time
        set_time_limit(0);

        $objectManager = $this
            ->getServiceLocator()
            ->get('Doctrine\ORM\EntityManager');

        $client = new \Zend\Json\Server\Client($this->config['bitcoind']['server']);
        $client->getHttpClient()->setOptions(array('timeout' => 30));
        $blockcount = $client->call('getblockcount');

        // Get the last block in the DB
        $query = $objectManager->createQuery('SELECT b FROM Blockchain\Entity\Block b WHERE b.id = (SELECT MAX(bm.id) FROM Blockchain\Entity\Block bm)');
        $result = $query->getResult();
        if (count($result) == 1) {
            $blockEntity = $result[0];
            $blockId = $blockEntity->getId();
            $blockhash = $blockEntity->getBlockhash();
            $blockNumber = $blockEntity->getBlockNumber();

            $block = $this->getBlock($blockhash);

            // remove last block and all associated transactions in case it wasn't loaded full or there was no "nextblockhash"
            $objectManager->remove($blockEntity);
            $objectManager->flush();
            
            $coinbaseExp = floor(($blockNumber) / 210000);
            $coinbaseValue = 50 / pow(2, $coinbaseExp);
        } else {
            $blockNumber = 0;
            $blockhash = $client->call('getblockhash', array($blockNumber));
            $coinbaseValue = 50;
        }

        $batchSize = 25; 

        $count = 0;
        // Start importing 
        while($blockhash) {
            if ($blockNumber % 210000 == 0) {
                // only calculate this when necessary instead of every loop
                $coinbaseExp = floor(($blockNumber) / 210000);
                $coinbaseValue = 50 / pow(2, $coinbaseExp);
            }
            
            echo "Importing Block: $blockNumber\n";

            $totalValue = 0;

            $block = $this->getBlock($blockhash);

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
            $objectManager->persist($blockEntity);
            $count++;

            $offeredFees = 0;
            $takenFees = 0;

            // First block is unique
            if ($blockNumber > 0) {
                $seenTxids = array();
                foreach ($block['tx'] as $txid) {
                    // the JSON RPC client appears to have a memory leak, so isolate it inside a function
                    $transaction = $this->getRawTransaction($txid);
                    
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
                    $objectManager->persist($transactionEntity);
                    $count++;

                    $inputValue = 0;
                    foreach ($transaction['vin'] as $input) {
                        $inputEntity = new \Blockchain\Entity\Input();
                        $inputEntity->setTxid($txid);
                        $inputEntity->setSequence($input['sequence']);
                        if (isset($input['coinbase'])) {
                            $inputEntity->setCoinbase($input['coinbase']);
                            $inputEntity->setValue($coinbaseValue);
                            $inputValue = $inputValue + $coinbaseValue;
                        } else {
                            if (isset($seenTxids[$input['txid']])) {
                                // input of one transaction is referencing the output of another transaction in the same block.  flush writes.
                                $objectManager->flush();
                                $objectManager->clear();
                                $blockEntity = $objectManager->getRepository('Blockchain\Entity\Block')->findOneBy(array('blockhash' => $blockhash));
                                $transactionEntity = $objectManager->getRepository('Blockchain\Entity\Transaction')->findOneBy(array('txid' => $txid));
                            }
                            echo 'txid: '.$txid.', input txid: '.$input['txid'].', vout: '.$input['vout']."\n";
                            $inputEntity->setInputTxid($input['txid']);
                            $inputEntity->setScriptSigAsm($input['scriptSig']['asm']);
                            $inputEntity->setScriptSigHex($input['scriptSig']['hex']);
                            $inputEntity->setVout($input['vout']);
                            $previousOutputEntity = $objectManager->getRepository('Blockchain\Entity\Output')->findOneBy(array('txid' => $input['txid'], 'n' => $input['vout']));
                            if (!$previousOutputEntity) {
                                die('could not find output');
                            }
                            $inputEntity->setValue($previousOutputEntity->getValue());
                            $inputEntity->setAddress($previousOutputEntity->getAddress());
                            $inputValue = $inputValue + $inputEntity->getValue();
                            // Need to figure out how to extract hash160, if it is of any value...
                            // $inputEntity->setHash160();
                        }
                        $inputEntity->setTransaction($transactionEntity);
                        $objectManager->persist($inputEntity);
                        $count++;
                    }

                    $totalValue = $totalValue + $inputValue;

                    $outputValue = 0;
                    foreach ($transaction['vout'] as $output) {
                        $outputEntity = new \Blockchain\Entity\Output();
                        $outputEntity->setTxid($txid);
                        $outputEntity->setN($output['n']);
                        $outputEntity->setValue($output['value']);
                        $outputValue = $outputValue + $output['value'];
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
                        $objectManager->persist($outputEntity);
                        $count++;
                    }

                    if (isset($input['coinbase'])) {
                        $takenFees = round($outputValue - $coinbaseValue, 8);
                    }

                    if (!isset($input['coinbase'])) {
                        $fee = round($inputValue - $outputValue, 8);
                        $offeredFees = $offeredFees + $fee;
                    } else {
                        $fee = 0;
                    }
                    $transactionEntity->setFee($fee);
                    $objectManager->persist($transactionEntity);
                    $count++;

                    $seenTxids[$txid] = true;

                    if ($count % $batchSize == 0) {
                        $objectManager->flush();
                        $objectManager->clear();
                        $blockEntity = $objectManager->getRepository('Blockchain\Entity\Block')->findOneBy(array('blockhash' => $blockhash));
                    }
                }
            }

            $offeredFees = round($offeredFees, 8);
            if ($offeredFees > $takenFees) {
                echo "WARNING: Possible lost coins. Offered Fees: $offeredFees, Taken Fees: $takenFees\n";
            }
            if ($takenFees > $offeredFees) {
                die("ERROR.  Impossible transaction: Offered Fees: $offeredFees, Taken Fees: $takenFees\n");
            }
            
            $blockEntity->setOfferedFees($offeredFees);
            $blockEntity->setTakenFees($takenFees);
            $blockEntity->setTotalvalue($totalValue);
            $blockEntity->setLostvalue(round($offeredFees - $takenFees, 8));
            $objectManager->persist($blockEntity);
            $count++;

            if (isset($block['nextblockhash'])) {
                $nextblockhash = $block['nextblockhash'];
            } else {
                $nextblockhash = null;
            }

            $objectManager->flush();
            $objectManager->clear();

            $blockhash = $nextblockhash;
            $blockNumber++;
        }
        
        return new ViewModel();
    }/*}}}*/

    // the JSON RPC client appears to have a memory leak, so isolate it inside a function
    protected function getBlock($hash)
    {/*{{{*/
        $client = new \Zend\Json\Server\Client($this->config['bitcoind']['server']);
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
    protected function getTransaction($hash)
    {/*{{{*/
        $client = new \Zend\Json\Server\Client($this->config['bitcoind']['server']);
        $client->getHttpClient()->setOptions(array('timeout' => 30));

        $retryCount = 3;
        $transaction = null;
        do {
            $queryFailed = false;
            try {
                $transaction = $client->call('gettransaction', array($hash));
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

    // the JSON RPC client appears to have a memory leak, so isolate it inside a function
    protected function getRawTransaction($hash)
    {/*{{{*/
        $client = new \Zend\Json\Server\Client($this->config['bitcoind']['server']);
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

