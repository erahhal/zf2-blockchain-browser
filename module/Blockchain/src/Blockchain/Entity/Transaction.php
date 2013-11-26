<?php

namespace Blockchain\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Transaction
 *
 * @ORM\Entity
 * @ORM\Table(
       name="Transaction",
       uniqueConstraints={
           @ORM\UniqueConstraint(name="txid_unique",columns={"txid"})
       },
       indexes={
           @ORM\Index(name="txid_idx", columns={"txid"}),
           @ORM\Index(name="block_id_idx", columns={"block_id"})
       }
   )
 */
class Transaction
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $txid;

    /**
     * block
     * note: Doctrine requires join to be on primary id
     *
     * @ORM\ManyToOne(targetEntity="Block", inversedBy="transactions", fetch="LAZY")
     * @ORM\JoinColumn(name="block_id", referencedColumnName="id", nullable=false)
     */
    protected $block;

    /**
     * inputs
     *
     * @ORM\OneToMany(targetEntity="Input", mappedBy="transaction", fetch="LAZY", cascade={"persist","remove"})
     */
    protected $inputs;

    /**
     * outputs
     *
     * @ORM\OneToMany(targetEntity="Output", mappedBy="transaction", fetch="LAZY", cascade={"persist","remove"})
     */
    protected $outputs;

    /**
     * @ORM\Column(type="integer")
     */
    private $version;

    /**
     * @ORM\Column(type="integer")
     */
    private $locktime;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $fee;

    /**
     * @ORM\Column(type="integer")
     */
    private $size;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->inputs = new \Doctrine\Common\Collections\ArrayCollection();
        $this->outputs = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set txid
     *
     * @param string $txid
     * @return Transaction
     */
    public function setTxid($txid)
    {
        $this->txid = $txid;

        return $this;
    }

    /**
     * Get txid
     *
     * @return string 
     */
    public function getTxid()
    {
        return $this->txid;
    }

    /**
     * Set blockhash
     *
     * @param string $blockhash
     * @return Transaction
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
     * Set block
     *
     * @param \Blockchain\Entity\Block $block
     * @return Transaction
     */
    public function setBlock(\Blockchain\Entity\Block $block = null)
    {
        $this->block = $block;

        return $this;
    }

    /**
     * Get block
     *
     * @return \Blockchain\Entity\Block 
     */
    public function getBlock()
    {
        return $this->block;
    }

    /**
     * Set version
     *
     * @param integer $version
     * @return Transaction
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
     * Set locktime
     *
     * @param integer $locktime
     * @return Transaction
     */
    public function setLocktime($locktime)
    {
        $this->locktime = $locktime;

        return $this;
    }

    /**
     * Get locktime
     *
     * @return integer 
     */
    public function getLocktime()
    {
        return $this->locktime;
    }

    /**
     * Set fee
     *
     * @param float $fee
     * @return Transaction
     */
    public function setFee($fee)
    {
        $this->fee = $fee;

        return $this;
    }

    /**
     * Get fee
     *
     * @return float 
     */
    public function getFee()
    {
        return $this->fee;
    }

    /**
     * Set size
     *
     * @param integer $size
     * @return Transaction
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
     * Add inputs
     *
     * @param \Blockchain\Entity\Input $inputs
     * @return Transaction
     */
    public function addInput(\Blockchain\Entity\Input $inputs)
    {
        $this->inputs[] = $inputs;

        return $this;
    }

    /**
     * Remove inputs
     *
     * @param \Blockchain\Entity\Input $inputs
     */
    public function removeInput(\Blockchain\Entity\Input $inputs)
    {
        $this->inputs->removeElement($inputs);
    }

    /**
     * Get inputs
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getInputs()
    {
        return $this->inputs;
    }

    /**
     * Add outputs
     *
     * @param \Blockchain\Entity\Output $outputs
     * @return Transaction
     */
    public function addOutput(\Blockchain\Entity\Output $outputs)
    {
        $this->outputs[] = $outputs;

        return $this;
    }

    /**
     * Remove outputs
     *
     * @param \Blockchain\Entity\Output $outputs
     */
    public function removeOutput(\Blockchain\Entity\Output $outputs)
    {
        $this->outputs->removeElement($outputs);
    }

    /**
     * Get outputs
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getOutputs()
    {
        return $this->outputs;
    }
}
