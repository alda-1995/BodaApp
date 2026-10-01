<?php

return [
    'dress_code' => [
        'enabled' => true,
        'title' => 'Código de Vestimenta',
        'fields' => [
            'title' => ['type' => 'text', 'label' => 'Título de la Sección', 'rules' => 'nullable|string|max:100'],
            'general_description' => ['type' => 'textarea', 'label' => 'Descripción General', 'rules' => 'nullable|string'],
            'type' => ['type' => 'text', 'label' => 'Tipo de Vestimenta (Ej: Formal)', 'rules' => 'nullable|string'],
            'men_description' => ['type' => 'text', 'label' => 'Vestimenta para Hombres', 'rules' => 'nullable|string'],
            'men_comments' => ['type' => 'text', 'label' => 'Comentarios Hombres', 'rules' => 'nullable|string'],
            'women_description' => ['type' => 'text', 'label' => 'Vestimenta para Mujeres', 'rules' => 'nullable|string'],
            'women_comments' => ['type' => 'text', 'label' => 'Comentarios Mujeres', 'rules' => 'nullable|string'],
        ]
    ],
    'gift_registry' => [
        'enabled' => true,
        'title' => 'Mesa de Regalos',
        'fields' => [
            'title' => ['type' => 'text', 'label' => 'Título Principal', 'rules' => 'nullable|string|max:100'],
            'general_description' => ['type' => 'textarea', 'label' => 'Descripción General', 'rules' => 'nullable|string'],
            'bank_name' => ['type' => 'text', 'label' => 'Nombre del Banco', 'rules' => 'nullable|string'],
            'bank_holder' => ['type' => 'text', 'label' => 'Titular de la Cuenta', 'rules' => 'nullable|string'],
            'bank_account' => ['type' => 'text', 'label' => 'Número de Cuenta', 'rules' => 'nullable|numeric'],
            'bank_clabe' => ['type' => 'text', 'label' => 'CLABE Interbancaria', 'rules' => 'nullable|numeric|digits:18'],
            'amazon_url' => ['type' => 'url', 'label' => 'Enlace de Amazon', 'rules' => 'nullable|url'],
            'amazon_message' => ['type' => 'text', 'label' => 'Mensaje Opcional Amazon', 'rules' => 'nullable|string'],
            // Liverpool
            'liverpool_id' => ['type' => 'text', 'label' => 'Número de Evento Liverpool', 'rules' => 'nullable|string'],
            'liverpool_url' => ['type' => 'url', 'label' => 'Enlace de Liverpool', 'rules' => 'nullable|url'],
        ]
    ]
];