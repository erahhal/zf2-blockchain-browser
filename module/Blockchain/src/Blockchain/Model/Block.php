<?php
namespace Block\Model;

class Block
{
    public $id;
    public $hash;
    public $size;
    public $height;
    public $version;
    public $merkleroot;
    public $time;
    public $nonce;
    public $bits;
    public $difficulty;
    public $previousblockhash;
    public $nextblockhash;

    public function exchangeArray($data)
    {
        $this->id = (!empty($data['id'])) ? $data['id'] : null;
        $this->hash = (!empty($data['hash'])) ? $data['hash'] : null;
        $this->size = (!empty($data['size'])) ? $data['size'] : null;
        $this->height = (!empty($data['height'])) ? $data['height'] : null;
        $this->version = (!empty($data['version'])) ? $data['version'] : null;
        $this->merkleroot = (!empty($data['merkleroot'])) ? $data['merkleroot'] : null;
        $this->time = (!empty($data['time'])) ? $data['time'] : null;
        $this->nonce = (!empty($data['nonce'])) ? $data['nonce'] : null;
        $this->bits = (!empty($data['bits'])) ? $data['bits'] : null;
        $this->difficulty = (!empty($data['difficulty'])) ? $data['difficulty'] : null;
        $this->previousblockhash = (!empty($data['previousblockhash'])) ? $data['previousblockhash'] : null;
        $this->nextblockhash = (!empty($data['nextblockhash'])) ? $data['nextblockhash'] : null;
    }
}
