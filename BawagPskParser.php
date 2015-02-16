<?php

namespace BawagPskParser;

/**
 * Class BawagPskParser
 *
 * @package BawagPskParser
 */
class BawagPskParser {

    /**
     * @param $content
     * 
     * @return mixed
     */
    public static function parse($content) {
        $lines = explode(PHP_EOL, $content);
        $data = array();
        foreach ($lines as $line) {
            if (strlen($line) === 0) {
                continue;
            }
            //$line = str_replace(["\n", "\t", "\r"],['', '', ''], $line);
            $csvLine = str_getcsv($line, ';');

            $date = new DateTime($csvLine[2]);

            // remove leading plus sign
            $csvLine[4] = ltrim($csvLine[4], '+$');
            // remove dot, replace comma by dot.
            $csvLine[4] = str_replace('.', '', $csvLine[4]);
            $csvLine[4] = str_replace(',', '.', $csvLine[4]);

            $csvLine[6] = '';
            $csvLine[7] = '';
            $csvLine[8] = '';
            $csvLine[9] = '';
            $csvLine[10] = '';
            $csvLine[11] = '';

            // todo: check https://github.com/PPOE/pgacc/blob/master/import.php line 185


            // match id
            $id = '';
            $result = preg_match("@[A-Z]{2}/\d{9} @", $csvLine[1], $matches);
            if ($result === 1) {
                $id = trim($matches[0]);
            }
            $csvLine[7] = $id;

            // match iban
            // only matches DE and AT iban's for now.
            $iban = '';
            $result = preg_match("/ [A-Z]{2}\d{16,18} /", $csvLine[1], $matches);
            if ($result === 1 && \IsoCodes\Iban::validate(trim($matches[0]))) {
                $iban = trim($matches[0]);
            } else {
                if ($date->format('Y') < 2014) {
                    $result = preg_match("/ \d{11} /", $csvLine[1], $matches);
                    if ($result === 1) {
                        $iban = trim($matches[0]);
                    }
                }
            }
            $csvLine[10] = $iban;

            // before id
            if (strlen($id)> 0) {
                $split = explode($id, $csvLine[1], 2);
                if (isset($split[0])) {
                    $csvLine[6] = trim($split[0]);
                }
            }

            // after id and before iban
            if (isset($split[1]) && strlen($iban) > 0) {
                $split2 = explode($iban, $split[1], 2);
                if (isset($split2[0])) {
                    $csvLine[8] = trim($split2[0]);
                    $result = preg_match("/ ([a-zA-Z]){4}([a-zA-Z]){2}([0-9a-zA-Z]){2}([0-9a-zA-Z]{3})? /", $split2[0], $matches);
                    $result2 = preg_match("/ ([0-9]){5} /", $split2[0], $matches2);
                    if ($result === 1 && \IsoCodes\SwiftBic::validate(trim($matches[0]))) {
                        $csvLine[9] = trim($matches[0]);
                    } else if ($result2 === 1 && $date->format('Y') < 2014) {
                        $csvLine[9] = trim($matches2[0]);
                    }
                }
            }

            // after iban
            if (strlen($iban) > 0) {
                $split = explode($iban, $csvLine[1], 2);
                if (isset($split[1])) {
                    $csvLine[11] = trim($split[1]);
                }
            }

            // extract name from before bankleitzahl
            if (strlen($csvLine[11]) === 0 && $csvLine[8] && $csvLine[9] && $date->format('Y') < 2014) {
                $split = explode($csvLine[9], $csvLine[8], 2);
                if (isset($split[0])) {
                    $csvLine[11] = trim($split[0]);
                }
            }

            // remove duplicate text
            if ($csvLine[6] && $csvLine[11]) {
                $csvLine[11] = trim(str_replace($csvLine[6], '', $csvLine[11]));
            }

            $data[] = $csvLine;
        }

        return $data;
    }
}