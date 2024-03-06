<?php
function stylizeText($text, $style) {
    switch ($style) {
        case 'bold':
            return "\002$text\002";
        case 'underline':
            return "\037$text\037";
        case 'italic':
            return "\035$text\035";
        case 'color_white':
            return "\00300$text\003";
        case 'color_black':
            return "\00301$text\003";
        case 'color_blue':
            return "\00302$text\003";
        case 'color_green':
            return "\00303$text\003";
        case 'color_red':
            return "\00304$text\003";
        case 'color_brown':
            return "\00305$text\003";
        case 'color_purple':
            return "\00306$text\003";
        case 'color_orange':
            return "\00307$text\003";
        case 'color_yellow':
            return "\00308$text\003";
        case 'color_light_green':
            return "\00309$text\003";
        case 'color_teal':
            return "\00310$text\003";
        case 'color_cyan':
            return "\00311$text\003";
        case 'color_light_blue':
            return "\00312$text\003";
        case 'color_pink':
            return "\00313$text\003";
        case 'color_grey':
            return "\00314$text\003";
        case 'color_light_grey':
            return "\00315$text\003";
        case 'bg_white':
            return "\00300,01$text\003";
        case 'bg_black':
            return "\00301,01$text\003";
        case 'bg_blue':
            return "\00302,01$text\003";
        case 'bg_green':
            return "\00303,01$text\003";
        case 'bg_red':
            return "\00304,01$text\003";
        case 'bg_brown':
            return "\00305,01$text\003";
        case 'bg_purple':
            return "\00306,01$text\003";
        case 'bg_orange':
            return "\00307,01$text\003";
        case 'bg_yellow':
            return "\00308,01$text\003";
        case 'bg_light_green':
            return "\00309,01$text\003";
        case 'bg_teal':
            return "\00310,01$text\003";
        case 'bg_cyan':
            return "\00311,01$text\003";
        case 'bg_light_blue':
            return "\00312,01$text\003";
        case 'bg_pink':
            return "\00313,01$text\003";
        case 'bg_grey':
            return "\00314,01$text\003";
        case 'bg_light_grey':
            return "\00315,01$text\003";
        // Add more cases for other styles, colors, and background colors as needed
        default:
            return $text; // No formatting
    }
    return $text;
}