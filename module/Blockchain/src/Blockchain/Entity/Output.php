<?php

namespace Blockchain\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Output
 *
 * @ORM\Entity
 * @ORM\Table(
       name="Output",
       uniqueConstraints={
           @ORM\UniqueConstraint(name="txid_n_unique",columns={"txid", "n"})
       },
       indexes={
           @ORM\Index(name="txid_idx", columns={"txid"}),
           @ORM\Index(name="hash160_idx", columns={"hash160"}),
           @ORM\Index(name="address_idx", columns={"address"}),
           @ORM\Index(name="n_idx", columns={"n"})
       }
   )
 */
class Output
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
     * API: n
     *
     * @ORM\Column(name="n", type="integer")
     */
    private $n;

    /**
     * @ORM\Column(type="bigint")
     */
    private $value;

    /**
     * @ORM\Column(type="text")
     */
    private $scriptPubKeyAsm;

    /**
     * @ORM\Column(type="text")
     */
    private $scriptPubKeyHex;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $reqSigs;

    /**
     * @ORM\Column(type="string", length=40, nullable=true)
     */
    private $hash160;

    /**
     * @ORM\Column(type="string", length=34, nullable=true)
     */
    private $address;

    /**
     * type: pubkey, pubkeyhash
     *
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $type;

    /**
     * key
     *
     * @ORM\ManyToOne(targetEntity="Key", fetch="LAZY", cascade={"persist"})
     * @ORM\JoinColumn(name="key_id", referencedColumnName="id")
     */
    protected $key;

    /**
     * redeemingInput
     *
     * @ORM\OneToOne(targetEntity="Input", fetch="LAZY")
     * @ORM\JoinColumn(name="redeeming_input_id", referencedColumnName="id")
     */
    protected $redeemingInput;

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
     * @return Output
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
     * Set n
     *
     * @param integer $n
     * @return Output
     */
    public function setN($n)
    {
        $this->n = $n;

        return $this;
    }

    /**
     * Get n
     *
     * @return integer 
     */
    public function getN()
    {
        return $this->n;
    }

    /**
     * Set value
     *
     * @param bigint $value
     * @return Output
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
     * Set scriptPubKeyAsm
     *
     * @param string $scriptKubkeyAsm
     * @return Output
     */
    public function setScriptPubKeyAsm($scriptPubKeyAsm)
    {
        $this->scriptPubKeyAsm = $scriptPubKeyAsm;

        return $this;
    }

    /**
     * Get scriptPubKeyAsm
     *
     * @return string 
     */
    public function getScriptPubKeyAsm()
    {
        return $this->scriptPubKeyAsm;
    }

    /**
     * Set scriptPubKeyHex
     *
     * @param string $scriptPubKeyHex
     * @return Output
     */
    public function setScriptPubKeyHex($scriptPubKeyHex)
    {
        $this->scriptPubKeyHex = $scriptPubKeyHex;

        return $this;
    }

    /**
     * Get scriptPubKkeyHex
     *
     * @return string 
     */
    public function getScriptPubKkeyHex()
    {
        return $this->scriptPubKkeyHex;
    }

    /**
     * Set reqSigs
     *
     * @param integer $reqSigs
     * @return Output
     */
    public function setReqSigs($reqSigs)
    {
        $this->reqSigs = $reqSigs;

        return $this;
    }

    /**
     * Get reqSigs
     *
     * @return integer 
     */
    public function getReqSigs()
    {
        return $this->reqSigs;
    }

    /**
     * Set hash160
     *
     * @param string $hash160
     * @return Output
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
     * Set type
     *
     * @param string $type
     * @return Output
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string 
     */
    public function getType()
    {
        return $this->type;
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

    /**
     * Set redeemingInput
     *
     * @param \Blockchain\Entity\Input $redeemingInput
     * @return Output
     */
    public function setRedeemingInput(\Blockchain\Entity\Input $redeemingInput = null)
    {
        $this->redeemingInput = $redeemingInput;

        return $this;
    }

    /**
     * Get redeemingInput
     *
     * @return \Blockchain\Entity\Transaction 
     */
    public function getRedeemingInput()
    {
        return $this->redeemingInput;
    }

    /**
     * Set transaction
     *
     * @param \Blockchain\Entity\Transaction $transaction
     * @return Output
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
