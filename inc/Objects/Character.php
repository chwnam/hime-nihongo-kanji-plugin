<?php

namespace HimeNihongo\KanjiPlugin\Objects;

class Character
{
    public int    $id      = 0;
    public string $kanji   = '';
    public string $kunYomi = '';
    public string $onYomi  = '';
    public string $kunKo   = '';
    public string $onKo    = '';
    public string $koExtra = '';
    public int    $level   = 0;

    public static function fromArray(array|object $items): static
    {
        $items = (array)$items;
        $out   = new static();

        foreach ($items as $key => $values) {
            if (property_exists($out, $key)) {
                $out->$key = $values;
            }
        }

        return $out;
    }
}
