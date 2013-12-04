<?php

namespace Blockchain\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Input
 *
 * @ORM\Entity
 * @ORM\Table(
       name="Input",
       uniqueConstraints={
           @ORM\UniqueConstraint(name="redeemedTxid_vout_unique",columns={"redeemedTxid", "vout"})
       },
       indexes={
           @ORM\Index(name="txid_idx", columns={"txid"}),
           @ORM\Index(name="address_idx", columns={"address"}),
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
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $redeemedTxid;

    /**
     * redeemedOutput
     *
     * @ORM\OneToOne(targetEntity="Output", fetch="LAZY")
     * @ORM\JoinColumn(name="redeemed_output_id", referencedColumnName="id")
     */
    protected $redeemedOutput;

    /**
     * transaction
     * note: Doctrine requires join to be on primary id
     *
     * @ORM\ManyToOne(targetEntity="Transaction", inversedBy="inputs", fetch="LAZY")
     * @ORM\JoinColumn(name="transaction_id", referencedColumnName="id")
     */
    protected $transaction;

    /**
     * key
     * note: Doctrine requires join to be on primary id
     *
     * @ORM\ManyToOne(targetEntity="Key", fetch="LAZY")
     * @ORM\JoinColumn(name="key_id", referencedColumnName="id")
     */
    protected $key;

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
     * @ORM\Column(type="bigint")
     */
    private $value;

    /**
     * @ORM\Column(type="string", length=40, nullable=true)
     */
    private $hash160;

    /**
     * @ORM\Column(type="string", length=34, nullable=true)
     */
    private $address;

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
     * Set redeemedTxid
     *
     * @param string $redeemedTxid
     * @return Input
     */
    public function setRedeemedTxid($redeemedTxid)
    {
        $this->redeemedTxid = $redeemedTxid;

        return $this;
    }

    /**
     * Get redeemedTxid
     *
     * @return string 
     */
    public function getRedeemedTxid()
    {
        return $this->redeemedTxid;
    }

    /**
     * Set redeemedOutput
     *
     * @param \Blockchain\Entity\Output $redeemedOutput
     * @return Input
     */
    public function setRedeemedOutput(\Blockchain\Entity\Output $redeemedOutput = null)
    {
        $this->redeemedOutput = $redeemedOutput;

        return $this;
    }

    /**
     * Get redeemedOutput
     *
     * @return \Blockchain\Entity\Output 
     */
    public function getRedeemedOutput()
    {
        return $this->redeemedOutpu;
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
     * @param bigint $value
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
     * @return bigint 
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
     * Set address
     *
     * @param string $address
     * @return Output
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return string 
     */
    public function getAddress()
    {
        return $this->address;
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

    /**
     * Set key
     *
     * @param \Blockchain\Entity\Key $key
     * @return Output
     */
    public function setKey(\Blockchain\Entity\Key $key = null)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Get key
     *
     * @return \Blockchain\Entity\Key 
     */
    public function getKey()
    {
        return $this->key;
    }
}
