<?php

namespace App\Utils;

/* Custom Spreadsheet Styles
   Author: Neil Daryl Sulit

   NOTE:
   => USE ARGB FORMAT FOR COLORS! //e.g. 'FFFF0000' -red
*/

class MyCustomStylesLibrary {
    function border_thin($color) { 
        return [
            'borders' => [
                'outline' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => $color], 
                ],
            ],
        ];
    }

    function border_thick($color){
        return [
            'borders' => [
                'outline' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                    'color' => ['argb' => $color], 
                ],
            ],
        ]; 
    }

    function alignment_horiz_right(){
        return [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
            ]
        ];
    }

    function alignment_horiz_center(){
        return [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ]
        ];
    }

    function alignment_horiz_left(){
        return [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
            ]
        ];
    }

    function fill_solid_color($color){
        return ['fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, //FILL_GRADIENT_LINEAR, 
                'startColor' => [
                    'argb' => $color,
                ],
            ],
        ];
    }
}