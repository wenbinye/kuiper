<?php
namespace kuiper\rpc\client\fixtures;

interface ApiServiceInterface
{
    /**
     * @param Request $request
     * @return Item[]
     */
    public function query(Request $request);
}
