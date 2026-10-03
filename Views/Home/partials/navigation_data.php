<?php
/**
 * Definición centralizada del catálogo y navegación en 7 pestañas principales
 * Mapeo 100% exacto con la base de datos:
 * momento_consumo (id_momento 1..5)
 * grupo_momento   (id_grupo 1..15)
 * categoria       (id_categoria 1..36)
 */
$menuMultinivel = [
    [
        'titulo'     => 'Frescos y Proteínas',
        'id_momento' => 1,
        'url'        => 'index.php?action=ver_categoria&id_momento=1&nombre=' . urlencode('Frescos y Proteínas'),
        'subcategorias' => [
            [
                'nombre'   => 'Frutas y Verduras',
                'id_grupo' => 1,
                'url'      => 'index.php?action=ver_categoria&id_grupo=1&nombre=' . urlencode('Frutas y Verduras'),
                'subitems' => [
                    ['nombre' => 'Frutas',   'id' => 1, 'url' => 'index.php?action=ver_categoria&id=1&nombre=' . urlencode('Frutas')],
                    ['nombre' => 'Verduras', 'id' => 2, 'url' => 'index.php?action=ver_categoria&id=2&nombre=' . urlencode('Verduras')],
                ]
            ],
            [
                'nombre'   => 'Carnes y Aves',
                'id_grupo' => 2,
                'url'      => 'index.php?action=ver_categoria&id_grupo=2&nombre=' . urlencode('Carnes y Aves'),
                'subitems' => [
                    ['nombre' => 'Pollo', 'id' => 3, 'url' => 'index.php?action=ver_categoria&id=3&nombre=' . urlencode('Pollo')],
                    ['nombre' => 'Res',   'id' => 4, 'url' => 'index.php?action=ver_categoria&id=4&nombre=' . urlencode('Res')],
                    ['nombre' => 'Cerdo', 'id' => 5, 'url' => 'index.php?action=ver_categoria&id=5&nombre=' . urlencode('Cerdo')],
                ]
            ],
            [
                'nombre'   => 'Pescados y Mariscos',
                'id_grupo' => 3,
                'url'      => 'index.php?action=ver_categoria&id_grupo=3&nombre=' . urlencode('Pescados y Mariscos'),
                'subitems' => [
                    ['nombre' => 'Pescados', 'id' => 6, 'url' => 'index.php?action=ver_categoria&id=6&nombre=' . urlencode('Pescados')],
                    ['nombre' => 'Mariscos', 'id' => 7, 'url' => 'index.php?action=ver_categoria&id=7&nombre=' . urlencode('Mariscos')],
                ]
            ],
        ]
    ],
    [
        'titulo'     => 'Lácteos y Huevos',
        'id_momento' => 2,
        'url'        => 'index.php?action=ver_categoria&id_momento=2&nombre=' . urlencode('Lácteos y Huevos'),
        'subcategorias' => [
            [
                'nombre'   => 'Lácteos',
                'id_grupo' => 4,
                'url'      => 'index.php?action=ver_categoria&id_grupo=4&nombre=' . urlencode('Lácteos'),
                'subitems' => [
                    ['nombre' => 'Leches',       'id' => 8,  'url' => 'index.php?action=ver_categoria&id=8&nombre=' . urlencode('Leches')],
                    ['nombre' => 'Yogures',      'id' => 9,  'url' => 'index.php?action=ver_categoria&id=9&nombre=' . urlencode('Yogures')],
                    ['nombre' => 'Mantequillas', 'id' => 10, 'url' => 'index.php?action=ver_categoria&id=10&nombre=' . urlencode('Mantequillas')],
                ]
            ],
            [
                'nombre'   => 'Quesos y Embutidos',
                'id_grupo' => 5,
                'url'      => 'index.php?action=ver_categoria&id_grupo=5&nombre=' . urlencode('Quesos y Embutidos'),
                'subitems' => [
                    ['nombre' => 'Quesos',             'id' => 11, 'url' => 'index.php?action=ver_categoria&id=11&nombre=' . urlencode('Quesos')],
                    ['nombre' => 'Jamones y Embutidos', 'id' => 12, 'url' => 'index.php?action=ver_categoria&id=12&nombre=' . urlencode('Jamones y Embutidos')],
                ]
            ],
            [
                'nombre'   => 'Huevos y Panadería',
                'id_grupo' => 6,
                'url'      => 'index.php?action=ver_categoria&id_grupo=6&nombre=' . urlencode('Huevos y Panadería'),
                'subitems' => [
                    ['nombre' => 'Huevos',           'id' => 13, 'url' => 'index.php?action=ver_categoria&id=13&nombre=' . urlencode('Huevos')],
                    ['nombre' => 'Panes y Tostadas', 'id' => 14, 'url' => 'index.php?action=ver_categoria&id=14&nombre=' . urlencode('Panes y Tostadas')],
                ]
            ],
        ]
    ],
    [
        'titulo'     => 'Abarrotes y Despensa',
        'id_momento' => 3,
        'url'        => 'index.php?action=ver_categoria&id_momento=3&nombre=' . urlencode('Abarrotes y Despensa'),
        'subcategorias' => [
            [
                'nombre'   => 'Granos y Menestras',
                'id_grupo' => 7,
                'url'      => 'index.php?action=ver_categoria&id_grupo=7&nombre=' . urlencode('Granos y Menestras'),
                'subitems' => [
                    ['nombre' => 'Arroz',                'id' => 15, 'url' => 'index.php?action=ver_categoria&id=15&nombre=' . urlencode('Arroz')],
                    ['nombre' => 'Menestras y Legumbres', 'id' => 16, 'url' => 'index.php?action=ver_categoria&id=16&nombre=' . urlencode('Menestras y Legumbres')],
                ]
            ],
            [
                'nombre'   => 'Aceites y Condimentos',
                'id_grupo' => 8,
                'url'      => 'index.php?action=ver_categoria&id_grupo=8&nombre=' . urlencode('Aceites y Condimentos'),
                'subitems' => [
                    ['nombre' => 'Aceites',           'id' => 17, 'url' => 'index.php?action=ver_categoria&id=17&nombre=' . urlencode('Aceites')],
                    ['nombre' => 'Salsas y Especias', 'id' => 18, 'url' => 'index.php?action=ver_categoria&id=18&nombre=' . urlencode('Salsas y Especias')],
                ]
            ],
            [
                'nombre'   => 'Pastas y Conservas',
                'id_grupo' => 9,
                'url'      => 'index.php?action=ver_categoria&id_grupo=9&nombre=' . urlencode('Pastas y Conservas'),
                'subitems' => [
                    ['nombre' => 'Fideos',               'id' => 19, 'url' => 'index.php?action=ver_categoria&id=19&nombre=' . urlencode('Fideos')],
                    ['nombre' => 'Conservas de Pescado', 'id' => 20, 'url' => 'index.php?action=ver_categoria&id=20&nombre=' . urlencode('Conservas de Pescado')],
                ]
            ],
        ]
    ],
    [
        'titulo'     => 'Bebidas y Snacks',
        'id_momento' => 4,
        'url'        => 'index.php?action=ver_categoria&id_momento=4&nombre=' . urlencode('Bebidas y Snacks'),
        'subcategorias' => [
            [
                'nombre'   => 'Bebidas',
                'id_grupo' => 10,
                'url'      => 'index.php?action=ver_categoria&id_grupo=10&nombre=' . urlencode('Bebidas'),
                'subitems' => [
                    ['nombre' => 'Gaseosas',       'id' => 21, 'url' => 'index.php?action=ver_categoria&id=21&nombre=' . urlencode('Gaseosas')],
                    ['nombre' => 'Aguas',          'id' => 22, 'url' => 'index.php?action=ver_categoria&id=22&nombre=' . urlencode('Aguas')],
                    ['nombre' => 'Frugos y Jugos', 'id' => 23, 'url' => 'index.php?action=ver_categoria&id=23&nombre=' . urlencode('Frugos y Jugos')],
                    ['nombre' => 'Energizantes',   'id' => 24, 'url' => 'index.php?action=ver_categoria&id=24&nombre=' . urlencode('Energizantes')],
                ]
            ],
            [
                'nombre'   => 'Snacks y Golosinas',
                'id_grupo' => 11,
                'url'      => 'index.php?action=ver_categoria&id_grupo=11&nombre=' . urlencode('Snacks y Golosinas'),
                'subitems' => [
                    ['nombre' => 'Papas y Piqueos', 'id' => 25, 'url' => 'index.php?action=ver_categoria&id=25&nombre=' . urlencode('Papas y Piqueos')],
                    ['nombre' => 'Galletas',        'id' => 26, 'url' => 'index.php?action=ver_categoria&id=26&nombre=' . urlencode('Galletas')],
                    ['nombre' => 'Chocolates',      'id' => 27, 'url' => 'index.php?action=ver_categoria&id=27&nombre=' . urlencode('Chocolates')],
                ]
            ],
        ]
    ],
    [
        'titulo'     => 'Otros',
        'id_momento' => 5,
        'url'        => 'index.php?action=ver_categoria&id_momento=5&nombre=' . urlencode('Otros'),
        'subcategorias' => [
            [
                'nombre'   => 'Limpieza del Hogar',
                'id_grupo' => 12,
                'url'      => 'index.php?action=ver_categoria&id_grupo=12&nombre=' . urlencode('Limpieza del Hogar'),
                'subitems' => [
                    ['nombre' => 'Detergentes',    'id' => 28, 'url' => 'index.php?action=ver_categoria&id=28&nombre=' . urlencode('Detergentes')],
                    ['nombre' => 'Lavavajillas',   'id' => 29, 'url' => 'index.php?action=ver_categoria&id=29&nombre=' . urlencode('Lavavajillas')],
                    ['nombre' => 'Desinfectantes', 'id' => 30, 'url' => 'index.php?action=ver_categoria&id=30&nombre=' . urlencode('Desinfectantes')],
                ]
            ],
            [
                'nombre'   => 'Cuidado Personal',
                'id_grupo' => 13,
                'url'      => 'index.php?action=ver_categoria&id_grupo=13&nombre=' . urlencode('Cuidado Personal'),
                'subitems' => [
                    ['nombre' => 'Jabones',                  'id' => 31, 'url' => 'index.php?action=ver_categoria&id=31&nombre=' . urlencode('Jabones')],
                    ['nombre' => 'Shampoo y Acondicionador', 'id' => 32, 'url' => 'index.php?action=ver_categoria&id=32&nombre=' . urlencode('Shampoo y Acondicionador')],
                    ['nombre' => 'Higiene Oral',             'id' => 33, 'url' => 'index.php?action=ver_categoria&id=33&nombre=' . urlencode('Higiene Oral')],
                ]
            ],
            [
                'nombre'   => 'Mascotas',
                'id_grupo' => 15,
                'url'      => 'index.php?action=ver_categoria&id_grupo=15&nombre=' . urlencode('Mascotas'),
                'subitems' => [
                    ['nombre' => 'Alimento para Perros', 'id' => 35, 'url' => 'index.php?action=ver_categoria&id=35&nombre=' . urlencode('Alimento para Perros')],
                    ['nombre' => 'Alimento para Gatos',  'id' => 36, 'url' => 'index.php?action=ver_categoria&id=36&nombre=' . urlencode('Alimento para Gatos')],
                ]
            ],
        ]
    ]
];

/**
 * Función auxiliar para verificar si un ítem o grupo está activo
 */
if (!function_exists('evaluarEstadoNavegacion')) {
    function evaluarEstadoNavegacion($item, $tipo, $params) {
        $action = $params['action'] ?? 'home_usuario';
        if ($action !== 'ver_categoria') return false;
        if (!empty($params['es_ofertas'])) return false;

        $id = isset($params['id']) && $params['id'] !== null ? (int)$params['id'] : null;
        $idGrupo = isset($params['id_grupo']) && $params['id_grupo'] !== null ? (int)$params['id_grupo'] : null;
        $idMomento = isset($params['id_momento']) && $params['id_momento'] !== null ? (int)$params['id_momento'] : null;
        $activeMomentoId = isset($params['active_momento_id']) ? (int)$params['active_momento_id'] : null;

        $nombre = mb_strtolower(trim($params['nombre'] ?? ''));
        $grupoNombre = mb_strtolower(trim($params['grupo_nombre'] ?? ''));
        $catNombre = mb_strtolower(trim($params['categoria_nombre'] ?? ''));

        if ($tipo === 'grupo') {
            if ($activeMomentoId !== null && isset($item['id_momento'])) {
                return ((int)$item['id_momento'] === $activeMomentoId);
            }
            return false;
        }

        if ($tipo === 'leaf') {
            // Si no se especificó un ID de hoja y estamos navegando a nivel de grupo o momento, ninguna hoja debe marcarse
            if (empty($id) && (!empty($idGrupo) || !empty($idMomento))) {
                return false;
            }
            if (!empty($item['id']) && $id && (int)$id === (int)$item['id']) {
                return true;
            }
            if ($catNombre && mb_strtolower($item['nombre']) === $catNombre) {
                return true;
            }
            if ($nombre && mb_strtolower($item['nombre']) === $nombre) {
                return true;
            }
            return false;
        }

        if ($tipo === 'subcat') {
            // Activo si algún subitem hoja está activo
            if (!empty($item['subitems'])) {
                foreach ($item['subitems'] as $s) {
                    if (evaluarEstadoNavegacion($s, 'leaf', $params)) {
                        return true;
                    }
                }
            }
            // Si el usuario navegó únicamente a una macrocategoría (id_momento) y no a un grupo o subcategoría, no activar subcat
            if (empty($idGrupo) && empty($id) && !empty($idMomento)) {
                return false;
            }
            // Coincidencia por id_grupo
            if (!empty($item['id_grupo']) && $idGrupo && (int)$idGrupo === (int)$item['id_grupo']) {
                return true;
            }
            if ($grupoNombre && mb_strtolower($item['nombre']) === $grupoNombre) {
                return true;
            }
            if ($nombre && mb_strtolower($item['nombre']) === $nombre) {
                return true;
            }
            return false;
        }

        return false;
    }
}
