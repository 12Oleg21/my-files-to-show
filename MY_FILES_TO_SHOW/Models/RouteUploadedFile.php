<?php

namespace app\models\asterisk;

use app\models\dialplan\routeEngine;
use Yii;
use yii\base\Model;

class RouteUploadedFile extends Model
{
    public $uploaded_file;

    public function rules()
    {
        return [
            [['uploaded_file'], 'file', 'skipOnEmpty' => true, 'checkExtensionByMimeType' => false, 'extensions' => 'csv', 'mimeTypes' => ['text/plain', 'text/csv']],
        ];
    }

    /**
     * Return CSV file with date to web-page
     * $date is asrray like this [['CID:'],['DIDs','Final Extension','Destination type','Destination','CallerID', 'Route_id', 'Route_name','Id'], ['5797883'], [5797884] ...]--> for IN template;
     * and [['DID :'],['Extensions', '#01','#02',...],['100'],['101'],[102],...] --> for OUT template
     * invoked from RoutesController actions Template_in,Template_out
     */
    public function outputCSV($data, $file_name = 'file.csv'): void
    {
        # output headers so that the file is downloaded rather than displayed
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=$file_name");
        # Disable caching - HTTP 1.1
        header("Cache-Control: no-cache, no-store, must-revalidate");
        # Disable caching - HTTP 1.0
        header("Pragma: no-cache");
        # Disable caching - Proxies
        header("Expires: 0");
        # Start the ouput
        $output = fopen("php://output", "w");
        # Then loop through the rows
        foreach ($data as $row) {
            # Add the rows to the body
            fputcsv($output, $row); // here you can change delimiter/enclosure
        }
        # Close the stream off
        fclose($output);
        exit;
    }

    /**
     * Get a template for outbound mass route check
     * The main function.
     * invoked from RoutesController action Template_out
     * @return array of date
     */
    public function get_template_out(): array
    {
        $array_for_csv = [];
        $header = ['DID:', 'ClientID:'];
        $all_prefixes = $this->get_all_prefixes();
        $all_extensions = $this->get_all_extensions();
        array_push($array_for_csv, $header, $all_prefixes);
        return array_merge($array_for_csv, $all_extensions);
    }

    /**
     * Get all extensions from OWN_extensions table
     * invoked from function get_template_out
     * @return array of extensions like [['100'],[101],[102],...]
     */
    public function get_all_extensions(): array
    {
        $all_extensions = [];
        $extension = new Extension();
        $extensions = $extension->find()->select('number')->asArray(true)->all();
        if (!empty($extensions)) {
            foreach ($extensions as $number) {
                $all_extensions[] = $number['number'];
            }
            sort($all_extensions);
            $all_extensions = array_chunk($all_extensions, 1);
        }
        return $all_extensions;
    }

    /**
     * Get all prefixes from route pattern lists
     * invoked from get_template_out()
     * @return array of prefixes like ['Extensions', 'no prefix', '#01', '#02', '#12', ...]
     */
    public function get_all_prefixes(): array
    {
        $patternlist = new PatternListOutboundEntry();
        $client_patternlist = new PatternListClientIdOutboundEntry();
        $all_prefixes_patternlist = $this->fill_out_array_of_prefixes($patternlist);
        $all_prefixes_client_patternlist = $this->fill_out_array_of_prefixes($client_patternlist, $all_prefixes_patternlist);
        $all_prefixes = array_merge($all_prefixes_patternlist, $all_prefixes_client_patternlist);
        $all_prefixes = array_unique($all_prefixes);
        $all_prefixes = array_filter($all_prefixes);
        array_unshift($all_prefixes, 'Extensions', 'no prefix');
        return $all_prefixes;
    }

    /**
     * @param $patternlist
     * @param $all_prefixes
     * @return array|mixed
     */
    public function fill_out_array_of_prefixes($patternlist, $all_prefixes = []): mixed
    {
        $prefixes = $patternlist->find()->select('prefix')->asArray(true)->all();
        return empty($prefixes) ? $all_prefixes : array_map(function ($prefix) {
            return $prefix['prefix'];
        }, $prefixes);
    }

    /**
     * Take all DID patterns from all pattern lists and CID of pool if it is used
     * invoked from RoutesController action Template_in
     * @return array of dids like [['5797883'], [67879900'], [''3524139], ...].
     */
    public function get_template_in(): array
    {
        $header = [['CID:'], ['DIDs', 'Final Extension', 'Destination type', 'Destination', 'CallerID', 'Route_id', 'Route_name', 'Type', 'Id']];
        $patternlist = new PatternListInboundEntry();
        $pool_cid = new DialerPoolCid();
        $patternlists = $patternlist->find()->all();
        $new_array_dids = [];
        if (!empty($patternlists)) {
            foreach ($patternlists as $pattern_line) {
                $new_array_dids = $this->pick_pattern_did_out($pattern_line, $new_array_dids);
                $new_array_dids = $this->pick_pool_did_out($pattern_line, $new_array_dids, $pool_cid);
            }
        }
        $new_array_dids = array_unique($new_array_dids);
        sort($new_array_dids);
        $out_array = array_chunk($new_array_dids, 1);
        return array_merge($header, $out_array);
    }

    /**
     * Pick dids out from pattern list
     * invoked from function get_template_in()
     * return array of DIDs
     */
    public function pick_pattern_did_out($pattern_line, $new_array_dids): mixed
    {
        if ($pattern_line->did_pattern != '') {
            $mod_did = $this->modify_did($pattern_line->did_pattern);
            $new_array_dids = array_merge($new_array_dids, $mod_did);
        }
        return $new_array_dids;
    }

    /**
     * Pick dids out from OWN_pool_cids table
     * invoked from function get_template_in()
     * @return array of DIDs
     */
    public function pick_pool_did_out($pattern_line, $new_array_dids, $pool_cid): array
    {
        if ($pattern_line->OWN_pool_id) {
            $pool_dids = $pool_cid->find()->select('cid')->where(['OWN_pool_id' => $pattern_line->OWN_pool_id])->asArray(true)->all();
            if (!empty($pool_dids)) {
                foreach ($pool_dids as $did) {
                    $new_array_dids[] = $did['cid'];
                }
            }
        }

        return $new_array_dids;
    }

    /**
     * Modify DID template, if it is in notation of asterisk
     * For example xx to 10-99 of z to 2-9 etc...
     * invoked function pick_pattern_did_out
     * @return array of all DIDs like ['10','11','12',...'99']
     */
    public function modify_did($did): array
    {
        $all_dids = [];
        if (preg_match('/[\.!]/', $did)) return []; // find the '.','!' symbols in $did
        if (is_numeric($did)) return [$did];
        $did_regexp = self::did_buildRegExpession($did);
        $arr_with_brackets = explode('[', $did_regexp);
        $main_number = $arr_with_brackets[0];
        $counted_brackets = count($arr_with_brackets);
        if ($counted_brackets == 3) {
            $last_symbol_in_1bracket = substr($arr_with_brackets[1], -1); // it must be ']'
            if ($last_symbol_in_1bracket == ']') {
                $first_bracket_digits = substr($arr_with_brackets[1], 0, -1);
                $arr_digits_in_1bracket = $this->unleash_range($first_bracket_digits);
                $last_symbol_in_2bracket = substr($arr_with_brackets[2], -1); // it must be ']'
                if ($last_symbol_in_2bracket == ']') {
                    $second_bracket_digits = substr($arr_with_brackets[2], 0, -1);
                    $arr_digits_in_2bracket = $this->unleash_range($second_bracket_digits);
                    if (is_numeric($main_number) or $main_number == '') {
                        $all_dids = $this->scroll_through_all_digits($main_number, $arr_digits_in_1bracket, $arr_digits_in_2bracket);
                    }
                }
            }
        } elseif ($counted_brackets == 2) {
            $last_symbol_in_1bracket = substr($arr_with_brackets[1], -1); // it must be ']'
            if ($last_symbol_in_1bracket == ']') {
                $first_bracket_digits = substr($arr_with_brackets[1], 0, -1);
                $arr_digits_in_1bracket = $this->unleash_range($first_bracket_digits);
                if (is_numeric($main_number) or $main_number == '') {
                    $all_dids = $this->сycle_by_numbers($main_number, $arr_digits_in_1bracket);
                }
            }
        }
        return $all_dids;
    }

    /**
     * @param $main_number
     * @param $arr_digits_in_bracket
     * @param $digit_1arr
     * @return array
     */
    public function сycle_by_numbers($main_number, $arr_digits_in_bracket, $digit_1arr = ''): array
    {
        $all_dids = [];
        foreach ($arr_digits_in_bracket as $digit_2arr) {
            $did = $main_number . $digit_1arr . $digit_2arr;
            $all_dids[] = $did;
        }
        return $all_dids;
    }

    /**
     * Forming all combinations for regular expression
     * for example 123[2-9][5-7] to array ['12325','12326','12327','12335'...'12397']
     * invoked function modify_did
     * @return array all combinations for regular expression
     */
    public function scroll_through_all_digits($main_number, $arr_digits_in_1bracket, $arr_digits_in_2bracket = []): array
    {
        $all_dids = [];
        foreach ($arr_digits_in_1bracket as $digit_1arr) {
            $dids = $this->сycle_by_numbers($main_number, $arr_digits_in_2bracket, $digit_1arr);
            $all_dids = array_merge($all_dids, $dids);
        }
        return $all_dids;
    }

    /**
     * Fill out the array by digits from bracket
     * for example '14-7' to [1,4,5,6,7]
     * invoked function modify_did
     * @return array of digits
     */
    public function unleash_range($bracket_digits): array
    {
        $arr_digits_in_bracket = [];
        $array_symbols = str_split($bracket_digits);
        $i = 0;
        while (($i < strlen($bracket_digits)) && ($bracket_digits != '')) {
            if (is_numeric($array_symbols[$i])) {
                array_push($arr_digits_in_bracket, $array_symbols[$i]);
            } elseif ($array_symbols[$i] == '-') {
                $start = $array_symbols[$i - 1];
                $stop = $array_symbols[$i + 1];
                $range_digit = range($start, $stop);
                $arr_digits_in_bracket = array_merge($arr_digits_in_bracket, $range_digit);
            }
            $i++;
        }
        if (!empty($arr_digits_in_bracket)) {
            $arr_digits_in_bracket = array_unique($arr_digits_in_bracket);
        }
        return $arr_digits_in_bracket;
    }

    /**
     * Builds regular expression from pattern in Astersik's dialplan format.
     * invoked from function modify_did
     * @param string $pattern
     * @return string
     */
    static function did_buildRegExpession($pattern): string
    {
        $pattern = trim($pattern);
        $p_array = str_split($pattern);
        $tmp = "";
        $expression = "";
        $regx_num = "/^\[[0-9]+(\-*[0-9])[0-9]*\]/i";
        $regx_alp = "/^\[[a-z]+(\-*[a-z])[a-z]*\]/i";
        // Try to build a Regular Expression from the dial pattern
        $i = 0;
        while (($i < strlen($pattern)) && ($pattern != '')) {
            switch (strtolower($p_array[$i])) {
                case 'x':
                    // Match any number between 0 and 9
                    $expression .= $tmp . "[0-9]";
                    $tmp = "";
                    break;
                case 'z':
                    // Match any number between 1 and 9
                    $expression .= $tmp . "[1-9]";
                    $tmp = "";
                    break;
                case 'n':
                    // Match any number between 2 and 9
                    $expression .= $tmp . "[2-9]";
                    $tmp = "";
                    break;
                case '[':
                    // Find out if what's between the brackets is a valid expression.
                    // If so, add it to the regular expression.
                    if (preg_match($regx_num, substr($pattern, $i), $matches) || preg_match($regx_alp, substr(strtolower($pattern), $i), $matches)) {
                        $bracket = $matches[0];
                        $digits = explode('-', $bracket);
                        $count = count($digits);
                        if ($count == 2) {
                            $first_range_digit = substr($digits[0], -1, 1);
                            $end_range_digit = substr($digits[1], 0, 1);
                            if ($first_range_digit > $end_range_digit) {
                                $bracket = substr($digits[0], 0, -1) . $end_range_digit . '-' . $first_range_digit . substr($digits[1], 1);
                            }
                        }
                        $expression .= $tmp . "" . $bracket;
                        $i = $i + (strlen($bracket) - 1);
                        $tmp = "";
                    }
                    break;
                default:
                    if (preg_match("/[0-9]/i", strtoupper($p_array[$i]))) {
                        $tmp .= strtoupper($p_array[$i]);
                    }
            }
            $i++;
        }
        $expression .= $tmp;
        return $expression;
    }

    /**
     * Save the upload file to /docs/route_csv/ directory
     * Check the header of this file
     * invoked from RoutesController action Upload
     * @return true or false
     */
    public function upload()
    {
        if ($this->uploaded_file == '') {
            $this->addError('uploaded_file', "Please upload a file");
            return false;
        }

        $file = Yii::$app->basePath . '/docs/route_csv/' . $this->uploaded_file->baseName . '.' . $this->uploaded_file->extension;
        if ($this->validate()) {
            $this->uploaded_file->saveAs($file);
            if ($this->headerCheck($file)) {
                return true;
            }
        }
        if (file_exists($file)) unlink($file);

        return false;
    }

    /**
     * Check if header of file includes words like CID or DID
     * and check if the first row includes number for checking
     * invoked from function upload
     * @return true or false
     */
    public function headerCheck($file): bool
    {
        if (($handle = fopen($file, "r")) !== FALSE) {
            $row = 1;
            while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
                if ($row == 1) {
                    $arr_first_line = explode(':', $data[0]);
                    $check_word = trim($arr_first_line[0]);
                    $number = isset($arr_first_line[1]) ? $arr_first_line[1] : false;
                    if (!$number) {
                        $this->addError('uploaded_file', "There is empty CID/DID in file, please check your file and try again!");
                        fclose($handle);
                        return false;
                    }
                    if ($check_word == 'DID' or $check_word == 'CID') {
                        $row++;
                    } else {
                        $this->addError('uploaded_file', "There is no template file");
                        fclose($handle);
                        return false;
                    }
                } elseif ($row == 2 and $check_word == 'DID') {
                    if ($data[0] == 'Extensions' and $data[1] == 'no prefix') {
                        fclose($handle);
                        return true;
                    } else {
                        $this->addError('uploaded_file', "Something is wrong with your file");
                        fclose($handle);
                        return false;
                    }
                } else {
                    fclose($handle);
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * The main function
     * Parse the CSV file and get result
     * Use function findPath for defining route.
     */
    public function mass_route_check(): array
    {
        $file = Yii::$app->basePath . '/docs/route_csv/' . $this->uploaded_file->baseName . '.' . $this->uploaded_file->extension;
        $array_for_csv = [];
        $row = 1;
        if (($handle = fopen($file, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
                if ($row == 1 and $data[0]) {
                    $arr_first_line = explode(':', $data[0]);
                    $check_word = trim($arr_first_line[0]);
                    $number = trim($arr_first_line[1]);
                    $array_for_csv = array_merge($array_for_csv, [$data]);
                    $clientID = null;
                    if ($data[1]) {
                        $client_array = explode(':', $data[1]);
                        $clientID = isset($client_array[1]) ? trim($client_array[1]) : null;
                    }
                    if ($check_word == 'CID') {
                        $type = 'IN';
                    } elseif ($check_word == 'DID') {
                        $type = 'OUT';
                    } else {
                        break;
                    }
                    $row++;
                    continue;
                }
                $num = count($data);
                if ($num == 0) {
                    $row++;
                    continue;
                }
                if ($type == 'IN') {
                    if ($row == 2) {
                        $array_for_csv[] = $data;
                    } else {
                        /** @var int $number */
                        $array_for_csv = $this->checking_routing_in($array_for_csv, $number, $data);
                    }
                } elseif ($type == 'OUT') {
                    if ($row == 2) {
                        array_shift($data);
                        $prefixes = $data;
                        $line_header = ['Extensions', 'Dialing', 'Final Extension', 'Destination type', 'Destination', 'CallerID', 'Route_id', 'Route_name', 'Type', 'Id'];
                        $array_for_csv[] = $line_header;
                        $row++;
                        continue;
                    }
                    if (isset($prefixes) and !empty($prefixes)) {
                        /** @var int $number */
                        /** @var int $clientID */
                        $array_for_csv = $this->checking_routing_out($array_for_csv, $number, $data, $prefixes, $clientID);
                    }
                } else {
                    break;
                }
                $row++;
            }
            fclose($handle);
            unlink($file);
        }
        return [$array_for_csv, $type];
    }

    /**
     * Get data using a function findPath from routeEngine class
     * forming array of data for csv file
     * invoked from function mass_route_check
     * @param $array_for_csv
     * @param $number
     * @param $data
     * @param $prefixes
     * @param null $clientID
     * @return array
     */
    public function checking_routing_out($array_for_csv, $number, $data, $prefixes, $clientID = null): array
    {
        $engine = new routeEngine();
        $extension = $data[0];
        $first_line_for_extention = true;
        foreach ($prefixes as $prefix) {
            $number_for_check = $prefix == 'no prefix' ? $number : $prefix . $number;
            $path = $engine->findPath(4, $number_for_check, $extension, null, $clientID);
            if (isset($path['pool_id']) and $path['pool_id']) $path['cid'] = "pool_{$path['pool_id']}";
            if ($path) {
                if ($first_line_for_extention) {
                    $path_in_csv = [$extension, $number_for_check, $path['extension'], $path['destination_type'], $path['destination'], trim($path['cid']), $path['route_id'], $path['name'], $path['route_type'], $path['id']];
                    $first_line_for_extention = false;
                } else {
                    $path_in_csv = ['', $number_for_check, $path['extension'], $path['destination_type'], $path['destination'], trim($path['cid']), $path['route_id'], $path['name'], $path['route_type'], $path['id']];
                }
                $array_for_csv[] = $path_in_csv;
            }
        }
        return $array_for_csv;
    }

    /**
     * Get data using a function findPath from routeEngine class
     * forming array of data for csv file
     * invoked from function mass_route_check
     * @param $array_for_csv
     * @param $number
     * @param $data
     * @return array
     */
    public function checking_routing_in($array_for_csv, $number, $data): array
    {
        $engine = new routeEngine();
        $path = $engine->findPath(5, $data[0], $number);
        if ($path) {
            $path_in_csv = [$data[0], $path['extension'], $path['destination_type'], $path['destination'], $path['cid'], $path['route_id'], $path['name'], $path['route_type'], $path['id']];
            $array_for_csv[] = $path_in_csv;
        }
        return $array_for_csv;
    }

}
