<?php

namespace App\Trait;

trait EnumOptions
{
    /**
     * Retorna um array associativo ['value' => 'label']
     */
    public static function options(): array
    {
        $options = [];
        
        foreach (self::cases() as $case) {

            $label = method_exists($case, 'label') ? $case->label() : $case->name;
            
            $options[$case->value] = $label;
        }
        
        return $options;
    }
}