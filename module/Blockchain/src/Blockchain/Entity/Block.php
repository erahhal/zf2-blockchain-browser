<?php

namespace Blockchain\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Block
 *
 * @ORM\Entity
 * @ORM\Table(
       name="Block",
       uniqueConstraints={
           @ORM\UniqueConstraint(name="blockhash_unique",columns={"blockhash"})
       },
       indexes={
           @ORM\Index(name="blockhash_idx", columns={"blockhash"}), 
           @ORM\Index(name="blockNumber_idx", columns={"blockNumber"}), 
           @ORM\Index(name="time_idx", columns={"time"})
       }
   )
 */
class Block
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * DB Ids are 1-indexed, but bitcoind blocks are 0-indexed
     *
     * @ORM\Column(type="integer")
     */
    private $blockNumber;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $blockhash;

    /**
     * @ORM\Column(type="integer")
     */
    private $size;

    /**
     * @ORM\Column(type="integer")
     */
    private $height;

    /**
     * @ORM\Column(type="integer")
     */
    private $version;

    /**
     * See: https://en.bitcoin.it/wiki/Merged_mining_specification#Merkle_Branch
     *
     * @ORM\Column(type="string", length=64)
     */
    private $merkleroot;

    /**
     * @ORM\Column(type="datetime")
     */
    private $time;

    /**
     * @ORM\Column(type="integer")
     */
    private $nonce;

    /**
     * @ORM\Column(type="string", length=8)
     */
    private $bits;

    /**
     * @ORM\Column(type="float")
     */
    private $difficulty;

    /**
     * @ORM\Column(type="float")
     */
    private $totalvalue;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $previousblockhash;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $nextblockhash;

    /**
     * transactions
     *
     * @ORM\OneToMany(targetEntity="Transaction", mappedBy="block", cascade={"persist"})
     * @ORM\OrderBy({"id" = "ASC"})
     */
    protected $transactions;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->transactions = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set blockNumber
     *
     * @param integer $blockNumber
     * @return Block
     */
    public function setBlockNumber($blockNumber)
    {
        $this->blockNumber = $blockNumber;

        return $this;
    }

    /**
     * Get blockNumber
     *
     * @return integer 
     */
    public function getBlockNumber()
    {
        return $this->blockNumber;
    }

    /**
     * Set blockhash
     *
     * @param string $blockhash
     * @return Block
     */
    public function setBlockhash($blockhash)
    {
        $this->blockhash = $blockhash;

        return $this;
    }

    /**
     * Get blockhash
     *
     * @return string 
     */
    public function getBlockhash()
    {
        return $this->blockhash;
    }

    /**
     * Set size
     *
     * @param integer $size
     * @return Block
     */
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Get size
     *
     * @return integer 
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Set height
     *
     * @param integer $height
     * @return Block
     */
    public function setHeight($height)
    {
        $this->height = $height;

        return $this;
    }

    /**
     * Get height
     *
     * @return integer 
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Set version
     *
     * @param integer $version
     * @return Block
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version
     *
     * @return integer 
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set merkleroot
     *
     * @param string $merkleroot
     * @return Block
     */
    public function setMerkleroot($merkleroot)
    {
        $this->merkleroot = $merkleroot;

        return $this;
    }

    /**
     * Get merkleroot
     *
     * @return string 
     */
    public function getMerkleroot()
    {
        return $this->merkleroot;
    }

    /**
     * Set time
     *
     * @param \DateTime $time
     * @return Block
     */
    public function setTime($time)
    {
        $this->time = $time;

        return $this;
    }

    /**
     * Get time
     *
     * @return \DateTime 
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * Set nonce
     *
     * @param integer $nonce
     * @return Block
     */
    public function setNonce($nonce)
    {
        $this->nonce = $nonce;

        return $this;
    }

    /**
     * Get nonce
     *
     * @return integer 
     */
    public function getNonce()
    {
        return $this->nonce;
    }

    /**
     * Set bits
     *
     * @param string $bits
     * @return Block
     */
    public function setBits($bits)
    {
        $this->bits = $bits;

        return $this;
    }

    /**
     * Get bits
     *
     * @return string 
     */
    public function getBits()
    {
        return $this->bits;
    }

    /**
     * Set difficulty
     *
     * @param $difficulty
     * @return Block
     */
    public function setDifficulty($difficulty)
    {
        $this->difficulty = $difficulty;

        return $this;
    }

    /**
     * Get difficulty
     *
     * @return \double 
     */
    public function getDifficulty()
    {
        return $this->difficulty;
    }

    /**
     * Set totalvalue
     *
     * @param $totalvalue
     * @return Block
     */
    public function setTotalvalue($totalvalue)
    {
        $this->totalvalue = $totalvalue;

        return $this;
    }

    /**
     * Get totalvalue
     *
     * @return \double 
     */
    public function getTotalvalue()
    {
        return $this->totalvalue;
    }

    /**
     * Set previousblockhash
     *
     * @param string $previousblockhash
     * @return Block
     */
    public function setPreviousblockhash($previousblockhash)
    {
        $this->previousblockhash = $previousblockhash;

        return $this;
    }

    /**
     * Get previousblockhash
     *
     * @return string 
     */
    public function getPreviousblockhash()
    {
        return $this->previousblockhash;
    }

    /**
     * Set nextblockhash
     *
     * @param string $nextblockhash
     * @return Block
     */
    public function setNextblockhash($nextblockhash)
    {
        $this->nextblockhash = $nextblockhash;

        return $this;
    }

    /**
     * Get nextblockhash
     *
     * @return string 
     */
    public function getNextblockhash()
    {
        return $this->nextblockhash;
    }

    /**
     * Add transaction
     *
     * @param \Blockchain\Entity\Transaction $transaction
     * @return Block
     */
    public function addTransaction(\Blockchain\Entity\Transaction $transaction)
    {
        $this->transactions[] = $transaction;

        return $this;
    }

    /**
     * Remove transaction
     *
     * @param \Blockchain\Entity\Transaction $transaction
     */
    public function removeTransaction(\Blockchain\Entity\Transaction $transaction)
    {
        $this->transactions->removeElement($transaction);
    }

    /**
     * Get transaction
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getTransactions()
    {
        return $this->transactions;
    }
}
