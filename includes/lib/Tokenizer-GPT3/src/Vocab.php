<?php

namespace Ze\TokenizerGpt3;
class Vocab
{
    private array $vocab;

    public function __construct(string $path = __DIR__ . '/pretrained_vocab_files/vocab.json')
    {
        $this->vocab = json_decode(file_get_contents($path), true);
    }

    public function data()
    {
        return $this->vocab;
    }
}
