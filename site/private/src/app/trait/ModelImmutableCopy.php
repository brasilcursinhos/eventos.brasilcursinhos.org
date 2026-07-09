<?php

namespace App\Trait;

trait ModelImmutableCopy
{
    /**
     * Cria uma nova instância da classe mesclando as propriedades atuais 
     * com os novos valores fornecidos.
     */
    protected function copy(array $overrides): self
    {
        $currentProps = get_object_vars($this);

        return new self(...array_merge($currentProps, $overrides));
    }
}