<?php

namespace Block\Model;

class BlockMapper
{
    protected $hydrator;
    protected $objectRepository;

    public function __construct($hydrator, $objectRepository)
    {
        $this->hydrator = $hydrator;
        $this->objectManager = $objectManager;
    }

    public function findAll()
    {
        $resultSet = $this->objectRepository->findAll();
        return $resultSet;
    }

    public function getBlockById($id)
    {
        $id  = (int) $id;
        $object = $this->objectRepository->findOneBy(array('id' => $id));
        if (!$object) {
            throw new \Exception("Could not find block number $id");
        }
        return $object;
    }

    public function getBlockByHash($hash)
    {
        $id  = (int) $id;
        $object = $this->objectRepository->findOneBy(array('hash' => $hash));
        if (!$object) {
            throw new \Exception("Could not find block with hash: $hash");
        }
        return $object;
    }

    public function saveBlock(Block $block)
    {
        $data = array(
                'artist' => $album->artist,
                'title'  => $album->title,
                );

        $id = (int) $album->id;
        if ($id == 0) {
            $this->tableGateway->insert($data);
        } else {
            if ($this->getAlbum($id)) {
                $this->tableGateway->update($data, array('id' => $id));
            } else {
                throw new \Exception('Album id does not exist');
            }
        }
    }

    public function deleteAlbum($id)
    {
        $this->tableGateway->delete(array('id' => (int) $id));
    }
}
