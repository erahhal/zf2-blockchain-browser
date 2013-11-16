<?php

namespace Blockchain\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Block
 *
 * @ORM\Entity
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
     * @ORM\Column(type="string")
     */
    private $hash;

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
     * @ORM\Column(type="string")
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
     * @ORM\Column(type="string")
     */
    private $bits;

    /**
     * @ORM\Column(type="float")
     */
    private $difficulty;

    /**
     * @ORM\Column(type="string")
     */
    private $previousblockhash;

    /**
     * @ORM\Column(type="string")
     */
    private $nextblockhash;


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
     * Set hash
     *
     * @param string $hash
     * @return Block
     */
    public function setHash($hash)
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * Get hash
     *
     * @return string 
     */
    public function getHash()
    {
        return $this->hash;
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
}
