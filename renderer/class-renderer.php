<?php

class DGCP_Renderer {
    private $data;
    private $options;

    public function __construct($info, $options = []) {
        $this->data = $info['payload']['content'] ?? [];

        $this->options = array_merge([
            'color_bg' => '#e1f1ff',
            'color_text' => '#1761c3',
            'color_text2' => '#1761c3',
            'color_buttom' => '#1761c4'
        ], $options);
    }

    public function render() {
        $opt = $this->options;

        $html = '<style>
            #accordionP {
                font-size: 14px;
            }

            #accordionP .card {
                border: 0;
                border-radius: 0;
            }

            #accordionP .card-header {
                border: none;
                border-radius: 0;
                padding: 0;
            }

            #accordionP .card-header.level-1:nth-of-type(odd) {
                background: ' . $opt['color_bg'] . ';
            }

            #accordionP .card-header.level-1:nth-of-type(even) {
                background: #c2e0ff;
            }

            #accordionP .card-header .text-blue a {
                display: block;
                width: 100%;
                padding: 1rem;
                font-weight: 600;
                color: ' . $opt['color_text'] . ';
                position: relative;
                text-decoration: none;
                transition: background 0.3s ease;
            }

            #accordionP .card-header .text-blue a:hover {
                background-color: #d0e9ff;
            }

            #accordionP .card-header .text-blue a:after {
                content: "\f068";
                font-family: "Font Awesome 5 Free";
                font-weight: 900;
                font-size: 1rem;
                position: absolute;
                right: 1rem;
                top: 50%;
                transform: translateY(-50%);
                color: ' . $opt['color_text'] . ';
            }

            #accordionP .card-header .text-blue a.collapsed:after {
                content: "\f067";
            }

            #accordionP .card-body {
                padding: 0.5rem;
                background-color: #fff;
            }

            #accordionP .jumbotron {
                background: #f6f7f7;
                padding: 1.5rem;
                margin-top: .5rem;
                margin-bottom: 0;
                border-radius: 0;
            }

            #accordionP .btn-primary {
                font-size: 1rem;
                font-weight: bold;
                width: 100%;
                padding: 0.5rem;
                background-color: ' . $opt['color_buttom'] . ';
                border: ' . $opt['color_buttom'] . ';
                transition: all 0.25s ease;
            }

            #accordionP .btn-primary:hover {
                transform: scale(1.05);
            }
        </style>';

        $html .= '<div class="accordion processes" id="accordionP">';

        foreach ($this->data as $modalidad => $anios) {
            $modalidad_id = $this->slugify($modalidad);
            $html .= $this->cardStart($modalidad_id, $modalidad, 'accordionP');

            krsort($anios);
            foreach ($anios as $anio => $meses) {
                $anio_id = $modalidad_id . '-' . $anio;
                $html .= $this->cardStart($anio_id, $anio, 'collapse-' . $modalidad_id);

                uksort($meses, [$this, 'compareMonths']);
                foreach ($meses as $mes => $procesos) {
                    $mes_id = $anio_id . '-' . $this->slugify($mes);
                    $html .= $this->cardStart($mes_id, $mes, 'collapse-' . $anio_id);

                    if ($modalidad === 'Casos de Excepción') {
                        foreach ($procesos as $tipo => $subprocesos) {
                            $tipo_id = $mes_id . '-' . $this->slugify($tipo);
                            $html .= $this->cardStart($tipo_id, $tipo, 'collapse-' . $mes_id);
                            foreach ($subprocesos as $subproceso) {
                                $html .= $this->renderProceso($subproceso);
                            }
                            $html .= $this->cardEnd();
                        }
                    } else {
                        foreach ($procesos as $proceso) {
                            $html .= $this->renderProceso($proceso);
                        }
                    }

                    $html .= $this->cardEnd();
                }

                $html .= $this->cardEnd();
            }

            $html .= $this->cardEnd();
        }

        $html .= '</div>';
        return $html;
    }

    private function renderProceso($proceso) {
        $date = new DateTime($proceso['fecha_publicacion']);

        $formatter = new IntlDateFormatter(
            'es_ES',
            IntlDateFormatter::FULL,
            IntlDateFormatter::NONE,
            null,
            IntlDateFormatter::GREGORIAN,
            "EEEE, d 'de' MMMM 'de' yyyy"
        );

        $date_output = $formatter->format($date);

        $html = '<div class="jumbotron archivos">';
        $html .= '<div class="row align-items-center">';
        $html .= '<div class="col">';
        $html .= '<h6><a class="text-dark" href="'.$proceso['url'].'" title="Ver" target="_blank">'.htmlspecialchars($proceso['codigo_proceso']).'</a></h6>';
        $html .= '<p class="mb-0 bold">' . htmlspecialchars($proceso['descripcion']) . '</p>';
        $html .= '<p class="mb-0" style="font-size: .85rem"><strong title="Estado del Proceso"><i class="fas fa-spinner"></i></strong> ' . htmlspecialchars($proceso['estado_proceso']) . '</p>';
        $html .= '<p class="mb-0" style="font-size: .85rem"><strong title="Fecha de Publicación"><i class="fas fa-calendar-alt"></i></strong> Publicado el ' . $date_output . '</p>';
        $html .= '</div><div class="col-3 mt-md-0">';
        $html .= '<a class="btn btn-primary btn-lg d-none d-md-block" href="' . $proceso['url'] . '" target="_blank" title="Detalles"><i class="fas fa-link"></i> DETALLES</a>';
        $html .= '<a class="btn btn-primary btn-lg d-block d-md-none" href="' . $proceso['url'] . '" target="_blank" title="Detalles"><i class="fas fa-2x fa-link"></i></a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    private function cardStart($id, $title, $parent_id) {
        $collapse_id = 'collapse-' . $id;
        $is_main = $parent_id === 'accordionP';
        $extra_class = $is_main ? ' level-1' : '';

        return '
        <div class="card">
            <div class="card-header parents' . $extra_class . '" id="heading-' . $id . '">
                <p class="title text-blue mb-0">
                    <a class="btn-block hover text-left collapsed" data-toggle="collapse" data-target="#' . $collapse_id . '" aria-expanded="false" aria-controls="' . $collapse_id . '">' . htmlspecialchars($title) . '</a>
                </p>
            </div>
            <div id="' . $collapse_id . '" class="collapse" aria-labelledby="heading-' . $id . '" data-parent="#' . $parent_id . '">
                <div class="card-body">';
    }

    private function cardEnd() {
        return '</div></div></div>';
    }

    private function slugify($text) {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $text)));
    }

    private function compareMonths($a, $b) {
        $months = [
            "Enero" => 1, "Febrero" => 2, "Marzo" => 3, "Abril" => 4,
            "Mayo" => 5, "Junio" => 6, "Julio" => 7, "Agosto" => 8,
            "Septiembre" => 9, "Octubre" => 10, "Noviembre" => 11, "Diciembre" => 12
        ];
        return ($months[$a] ?? 0) < ($months[$b] ?? 0) ? 1 : -1;
    }
}
