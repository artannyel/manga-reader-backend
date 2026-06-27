<?php

declare(strict_types=1);

return [
    'required' => 'O campo :attribute é obrigatório.',
    'email' => 'O campo :attribute deve ser um endereço de e-mail válido.',
    'unique' => 'O valor do campo :attribute já está em uso.',
    'confirmed' => 'A confirmação do campo :attribute não confere.',
    'integer' => 'O campo :attribute deve ser um número inteiro.',
    'string' => 'O campo :attribute deve ser uma string.',
    'uuid' => 'O campo :attribute deve ser um UUID válido.',
    'in' => 'O campo :attribute selecionado é inválido.',
    'min' => [
        'numeric' => 'O campo :attribute deve ser pelo menos :min.',
        'file' => 'O campo :attribute deve ter pelo menos :min kilobytes.',
        'string' => 'O campo :attribute deve ter pelo menos :min caracteres.',
        'array' => 'O campo :attribute deve ter pelo menos :min itens.',
    ],
    'max' => [
        'numeric' => 'O campo :attribute não pode ser maior que :max.',
        'file' => 'O campo :attribute não pode ter mais de :max kilobytes.',
        'string' => 'O campo :attribute não pode ter mais de :max caracteres.',
        'array' => 'O campo :attribute não pode ter mais de :max itens.',
    ],
    'custom' => [],
    'attributes' => [],
];
