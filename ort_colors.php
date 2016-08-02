<?php

function num2color($num){
    switch($num)
    {
        case '00':
            return 'Black';
        case '01':
            return 'Brown';
        case '02':
            return 'Red';
        case '03':
            return 'Orange';
        case '04':
            return 'Yellow';
        case '05':
            return 'Green';
        case '06':
            return 'Blue';
        case '07':
            return 'Purple';
        case '08':
            return 'Gray';
        case '09':
            return 'White';
        case '11':
            return 'Pink';
        case 'G3':
            return 'Go Daddy Orange';
        case 'G5':
            return 'Go Daddy Green';
        default:
            return 'Unknown Color';
    }
}

?>