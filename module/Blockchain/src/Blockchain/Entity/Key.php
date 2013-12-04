<?php

namespace Blockchain\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Key
 *
 * @ORM\Entity
 * @ORM\Table(
       name="`Key`",
       uniqueConstraints={
           @ORM\UniqueConstraint(name="hash160_unique",columns={"hash160"}),
           @ORM\UniqueConstraint(name="address_unique",columns={"address"}),
           @ORM\UniqueConstraint(name="pubkey_unique",columns={"pubkey"})
       },
       indexes={
           @ORM\Index(name="hash160_idx", columns={"hash160"}),
           @ORM\Index(name="address_idx", columns={"address"}),
           @ORM\Index(name="pubkey_idx", columns={"pubkey"}),
           @ORM\Index(name="firstblockhash_idx", columns={"firstblockhash"})
       }
   )
 */
class Key
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=40, nullable=true)
     */
    private $hash160;

    /**
     * @ORM\Column(type="string", length=34, nullable=true)
     */
    private $address;

    /**
     * @ORM\Column(type="string", length=130, nullable=true)
     */
    private $pubkey;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $firstblockhash;

    /**
     * firstblock
     *
     * @ORM\ManyToOne(targetEntity="Block", fetch="LAZY")
     * @ORM\JoinColumn(name="firstblock_id", referencedColumnName="id", nullable=false)
     */
    protected $firstblock;


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
     * Set hash160
     * @return Key
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
     * @return Key
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
     * Set pubkey
     *
     * @param string $pubkey
     * @return Key
     */
    public function setPubkey($pubkey)
    {
        $this->pubkey = $pubkey;

        return $this;
    }

    /**
     * Get pubkey
     *
     * @return string 
     */
    public function getPubkey()
    {
        return $this->pubkey;
    }

    /**
     * Set firstblockhash
     *
     * @param string $firstblockhash
     * @return Key
     */
    public function setFirstblockhash($firstblockhash)
    {
        $this->firstblockhash = $firstblockhash;

        return $this;
    }

    /**
     * Get firstblockhash
     *
     * @return string 
     */
    public function getFirstblockhash()
    {
        return $this->firstblockhash;
    }

    /**
     * Set firstblock
     *
     * @param \Blockchain\Entity\Block $firstblock
     * @return Key
     */
    public function setFirstblock(\Blockchain\Entity\Block $firstblock = null)
    {
        $this->firstblock = $firstblock;

        return $this;
    }

    /**
     * Get firstblock
     *
     * @return \Blockchain\Entity\Block 
     */
    public function getFirstblock()
    {
        return $this->firstblock;
    }
}
