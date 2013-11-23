<?php

namespace Blockchain\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Input
 *
 * @ORM\Entity
 * @ORM\Table(
       name="Input",
       indexes={
           @ORM\Index(name="txid_idx", columns={"txid"}),
           @ORM\Index(name="hash160_idx", columns={"hash160"})
       }
   )
 */
class Input
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $txid;

    /**
     * transaction
     * note: Doctrine requires join to be on primary id
     *
     * @ORM\ManyToOne(targetEntity="Transaction", inversedBy="inputs", fetch="LAZY")
     * @ORM\JoinColumn(name="transaction_id", referencedColumnName="id")
     */
    protected $transaction;

    /**
     * @ORM\Column(type="integer")
     */
    private $sequence;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $coinbase;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $scriptSigAsm;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $scriptSigHex;

    /**
     * API: vout
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $vout;

    /**
     * @ORM\Column(type="float")
     */
    private $value;

    /**
     * @ORM\Column(type="string", length=40, nullable=true)
     */
    private $hash160;



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
     * @return Input
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
     * Set sequence
     *
     * @param integer $sequence
     * @return Input
     */
    public function setSequence($sequence)
    {
        $this->sequence = $sequence;

        return $this;
    }

    /**
     * Get sequence
     *
     * @return integer 
     */
    public function getSequence()
    {
        return $this->sequence;
    }

    /**
     * Set coinbase
     *
     * @param string $coinbase
     * @return Input
     */
    public function setCoinbase($coinbase)
    {
        $this->coinbase = $coinbase;

        return $this;
    }

    /**
     * Get coinbase
     *
     * @return string 
     */
    public function getCoinbase()
    {
        return $this->coinbase;
    }

    /**
     * Set scriptSigAsm
     *
     * @param string $scriptSigAsm
     * @return Input
     */
    public function setScriptSigAsm($scriptSigAsm)
    {
        $this->scriptSigAsm = $scriptSigAsm;

        return $this;
    }

    /**
     * Get scriptSigAsm
     *
     * @return string 
     */
    public function getScriptSigAsm()
    {
        return $this->scriptSigAsm;
    }

    /**
     * Set scriptSigHex
     *
     * @param string $scriptSigHex
     * @return Input
     */
    public function setScriptSigHex($scriptSigHex)
    {
        $this->scriptSigHex = $scriptSigHex;

        return $this;
    }

    /**
     * Get scriptSigHex
     *
     * @return string 
     */
    public function getScriptSigHex()
    {
        return $this->scriptSigHex;
    }

    /**
     * Set vout
     *
     * @param integer $vout
     * @return Input
     */
    public function setVout($vout)
    {
        $this->vout = $vout;

        return $this;
    }

    /**
     * Get vout
     *
     * @return integer 
     */
    public function getVout()
    {
        return $this->vout;
    }

    /**
     * Set value
     *
     * @param float $value
     * @return Input
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return float 
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set hash160
     *
     * @param string $hash160
     * @return Input
     */
    public function setHash160($hash160)
    {
        $this->hash160 = $hash160;

        return $this;
    }

    /**
     * Get hash160
     *
     * @return string 
     */
    public function getHash160()
    {
        return $this->hash160;
    }

    /**
     * Set transaction
     *
     * @param \Blockchain\Entity\Transaction $transaction
     * @return Input
     */
    public function setTransaction(\Blockchain\Entity\Transaction $transaction = null)
    {
        $this->transaction = $transaction;

        return $this;
    }

    /**
     * Get transaction
     *
     * @return \Blockchain\Entity\Transaction 
     */
    public function getTransaction()
    {
        return $this->transaction;
    }
}
