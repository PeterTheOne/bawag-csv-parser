<?php

namespace BawagCsvParser;
use DateTime;

/**
 * Class BawagCsvParser
 *
 * @package BawagCsvParser
 */
class BawagCsvParser {

    /**
     * @param $content
     * @throws \Exception
     */
    public static function validateCsv($content) {
        if (!is_string($content)) {
            throw new \Exception('Argument is not a string.');
        }
        if (strlen($content) === 0) {
            throw new \Exception('Argument is an empty string.');
        }
        $lines = explode(PHP_EOL, $content);
        foreach ($lines as $line) {
            if (strlen($line) === 0) {
                continue;
            }
            $csvValues = str_getcsv($line, ';');
            if (count($csvValues) < 6) {
                throw new \Exception('Argument is an empty string.');
            }
        }
    }

    /**
     * @param $content
     *
     * @return mixed
     * @throws \Exception
     */
    public static function parse($content) {
        BawagCsvParser::validateCsv($content);

        $data = array();
        $lines = explode(PHP_EOL, $content);
        foreach ($lines as $line) {
            if (strlen($line) === 0) {
                continue;
            }
            $values = str_getcsv($line, ';');
            $data[] = BawagCsvParser::parseLine($values);
        }

        return $data;
    }

    private static function parseLine($values) {
        $entry = new \stdClass();
        BawagCsvParser::setPostingLineId($entry, $values);
        BawagCsvParser::setAccount($entry, $values);
        BawagCsvParser::setText($entry, $values);
        BawagCsvParser::setPostingDate($entry, $values);
        BawagCsvParser::setValueDate($entry, $values);
        BawagCsvParser::setAmount($entry, $values);
        BawagCsvParser::setCurrency($entry, $values);
        BawagCsvParser::setComment($entry, $values);
        BawagCsvParser::setContraAccount($entry, $values);
        BawagCsvParser::setContraBic($entry, $values);
        BawagCsvParser::setContraName($entry, $values);

        return $entry;
    }

    private static function setPostingLineId(&$entry, $values) {
        $result = preg_match("@[A-Z]{2}/\d{9}@", $values[1], $matches);
        if ($result === 1) {
            $entry->postingLineId = trim($matches[0]);
            return;
        }
        $entry->postingLineId = '';
    }

    private static function setAccount(&$entry, $values) {
        $entry->account = $values[0];
    }

    private static function setText(&$entry, $values) {
        $entry->text = $values[1];
    }

    private static function setPostingDate(&$entry, $values) {
        $entry->postingDate = $values[3];
    }

    private static function setValueDate(&$entry, $values) {
        $entry->valueDate = $values[2];
    }

    private static function setAmount(&$entry, $values) {
        // remove leading plus sign
        $entry->amount = ltrim($values[4], '+$');
        // remove dot, replace comma by dot.
        $entry->amount = str_replace('.', '', $entry->amount);
        $entry->amount = str_replace(',', '.', $entry->amount);
    }

    private static function setCurrency(&$entry, $values) {
        $entry->currency = $values[5];
    }

    private static function setComment(&$entry, $values) {
        // before id
        if (strlen($entry->postingLineId) > 0) {
            $split = explode($entry->postingLineId, $values[1], 2);
            if (isset($split[0])) {
                $entry->comment = trim($split[0]);
                return;
            }
        }
        $entry->comment = '';
    }

    private static function setContraAccount(&$entry, $values) {
        // match iban
        // only matches DE and AT iban's for now.
        $result = preg_match("/ [A-Z]{2}\d{14,20} /", $values[1], $matches);
        if ($result === 1 && \IsoCodes\Iban::validate(trim($matches[0]))) {
            $entry->contraAccount = trim($matches[0]);
            return;
        }

        $date = new DateTime($entry->valueDate);
        if ($date->format('Y') < 2014) {
            $result = preg_match("/ \d{11}/", $values[1], $matches);
            if ($result === 1) {
                $entry->contraAccount = trim($matches[0]);
                return;
            }
        }
        $entry->contraAccount = '';
    }

    private static function setContraBic(&$entry, $values) {
        $split = explode($entry->postingLineId, $values[1], 2);

        // after id and before iban
        if (isset($split[1]) && strlen($entry->contraAccount) > 0) {
            $split2 = explode($entry->contraAccount, $split[1], 2);
            if (isset($split2[0])) {
                $result = preg_match("/ ([a-zA-Z]){4}([a-zA-Z]){2}([0-9a-zA-Z]){2}([0-9a-zA-Z]{3})? /", $split2[0], $matches);
                $result2 = preg_match("/ ([0-9]){5} /", $split2[0], $matches2);
                $date = new DateTime($entry->valueDate);
                if ($result === 1 && \IsoCodes\SwiftBic::validate(trim($matches[0]))) {
                    $entry->contraBic = trim($matches[0]);
                    return;
                } else if ($result2 === 1 && $date->format('Y') < 2014) {
                    $entry->contraBic = trim($matches2[0]);
                    return;
                }
            }
        }
        $entry->contraBic = '';
    }

    private static function setContraName(&$entry, $values) {
        $contraName = '';

        // after contraAccount
        if (strlen($entry->contraAccount) > 0) {
            $split = explode($entry->contraAccount, $values[1], 2);
            if (isset($split[1])) {
                $contraName = trim($split[1]);
            }
        }
        // extract name from before bankleitzahl
        $date = new DateTime($entry->valueDate);
        if (strlen($contraName) === 0 && $entry->postingLineId && $entry->contraBic && $date->format('Y') < 2014) {
            $split = explode($entry->postingLineId, $values[1], 2);
            $split = explode($entry->contraBic, $split[1], 2);
            if (isset($split[0])) {
                $contraName = trim($split[0]);
            }
        }

        // remove duplicate text
        if ($entry->comment && $contraName) {
            $contraName = trim(str_replace($entry->comment, '', $contraName));
        }

        $entry->contraName = $contraName;
    }
}
