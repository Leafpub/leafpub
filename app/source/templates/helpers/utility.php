<?php
//
// Utility helpers for Handlebars
//
return [

    // Joins (concatenates) any number of strings into one
    'concat' => function() {
        $args = func_get_args();
        $options = end($args);

        $string = [];
        $delimiter = isset($options['hash']['delimiter']) ? $options['hash']['delimiter'] : '';

        for($i = 0; $i < count($args) - 1; $i++) {
            $string[] = (string) $args[$i];
        }

        return implode($delimiter, $string);
    },

    // Returns a formatted date
    'date' => function($date, $options = null) {
        // If only one argument was passed in, adjust options and set the default $date
        if(!$options) {
            $options = $date;

            // Try this.date
            if(isset($options['_this']['pub_date'])) {
                $date = $options['_this']['pub_date'];
            } else {
                // Fallback to the current date/time
                $date = date('Y-m-d H:i:s');
            }
        }

        // Determine format
        $format = isset($options['hash']['format']) ? $options['hash']['format'] : '%Y-%m-%d';

        return \Postleaf\Postleaf::strftime($format, strtotime($date));
    },

    // Uses the first truthy argument passed in
    'either' => function() {
        $args = func_get_args();
        $options = end($args);

        // Loop through each argument and look for a truthy value
        for($i = 0; $i < count($args) - 1; $i++) {
            if($args[$i]) {
                return $options['fn']($args[$i]);
            }
        }

        // Do {{else}} if there aren't any truthy values
        return $options['inverse'] ? $options['inverse']() : '';
    },

    // URL encodes a string
    'encode' => function($string) {
        return rawurlencode($string);
    },

    // Returns a plain-text excerpt
    'excerpt' => function($string, $options = null) {
        if(!$options) {
            $options = $string;

            // Try this.content
            if(isset($options['_this']['content'])) {
                $string = $options['_this']['content'];
            } else {
                return '';
            }
        }

        // Convert <br> to spaces so words don't get merged when we strip the tags
        $string = preg_replace('/\<br(\s*)?\/?\>/i', ' ', $string);

        // Remove HTML tags
        $string = strip_tags($string);

        // Get desired length
        $num_chars = (int) $options['hash']['characters'];
        $num_words = (int) $options['hash']['words'];

        // Truncate it
        if($num_chars) {
            $string = \Postleaf\Postleaf::getChars($string, $num_chars ? $num_chars : 140);
        } else {
            // Return first n words
            $string = \Postleaf\Postleaf::getWords($string, $num_words ? $num_words : 50);
        }

        // We've stripped HTML tags, so return as-is
        return new \LightnCandy\SafeString($string);
    },

    // Compares two values
    'is' => function() {
        $args = func_get_args();
        $options = end($args);

        switch(count($args) - 1) {
            // One variable
            case 1:
                $left = true;
                $right = !!$args[0];
                $operator = '==';
                break;

            // Two variables
            case 2:
                $left = $args[0];
                $operator = '==';
                $right = $args[1];
                break;

            // Two variables + operator
            case 3:
                $left = $args[0];
                $operator = $args[1];
                $right = $args[2];
        }

        // Compare values
        switch(strtolower($operator)) {
            case '>':
                $is = $left > $right;
                break;
            case '>=':
                $is = $left >= $right;
                break;
            case '<':
                $is = $left < $right;
                break;
            case '<=':
                $is = $left <= $right;
                break;
            case '===':
                $is = $left === $right;
                break;
            case '&&':
            case 'and':
                $is = $left && $right;
                break;
            case '||':
            case 'or':
                $is = ($left || $right);
                break;
            case 'xor':
                $is = ($left xor $right);
                break;
            case '!=':
            case 'not':
                $is = $left != $right;
                break;
            case '!==':
                $is = $left !== $right;
                break;
            case 'in':
            case 'not in':
                $is = true;
                if(!is_array($right)) {
                    // Split CSV into an array
                    $right = explode(',', (string) $right);
                    $right = array_map(function($a) {
                        return trim($a);
                    }, $right);
                }
                $is = in_array($left, $right);
                if(strtolower($operator) === 'not in') $is = !$is;
                break;
            case 'type':
                if($right === 'array') $is = is_array($left);
                if($right === 'string') $is = is_string($left);
                break;
            default:
                $is = $left == $right;
                break;
        }

        if($is) {
            return $options['fn']();
        } else {
            return $options['inverse'] ? $options['inverse']() : '';
        }
    },

    // Encodes data as a JSON string
    'json_encode' => function($data, $options) {
        return json_encode($data);
    },

    // Outputs localized terms from the current language pack
    'L' => function($term, $options) {
        return \Postleaf\Language::term($term, $options['hash']);
    },

    // Logs details to the screen
    'log' => function($var, $options = null) {
        return print_r($options ? $var  : $var['_this'], true);
    },

    // Convert markdown to HTML
    'markdown' => function($markdown, $options) {
        return \Postleaf\Postleaf::markdownToHtml($markdown);
    },

    // Compares two values using regex
    'match' => function($string, $regex, $options) {
        if(!!preg_match($regex, $string)) {
            return $options['fn']();
        } else {
            return $options['inverse'] ? $options['inverse']() : '';
        }
    },

    // Perform math operations
    'math' => function() {
        $args = func_get_args();
        $options = end($args);

        switch(count($args) - 1) {
            // One argument (number)
            case 1:
                return $args[0];

            // Two arguments (operator, number)
            case 2:
                switch($args[0]) {
                    case 'abs':
                        return abs($args[1]);
                    case 'ceil':
                        return ceil($args[1]);
                    case 'floor':
                        return floor($args[1]);
                    case 'round':
                        return round($args[1]);
                    case 'sqrt':
                        return sqrt($args[1]);
                }
                break;

            // Three arguments (number, operator, number)
            case 3:
                switch($args[1]) {
                    case '+':
                        return $args[0] + $args[2];
                    case '-':
                        return $args[0] - $args[2];
                    case '*':
                        return $args[0] * $args[2];
                    case '/':
                        return $args[2] === 0 ? '' : $args[0] / $args[2];
                    case '^':
                        return pow($args[0], $args[2]);
                    case '%':
                        return $args[0] % $args[2];
                }
                break;
        }
    },

    // Formats a number
    'number' => function($number, $options) {
        $places = isset($options['hash']['places']) ?
            $options['hash']['places'] : 0;

        $decimal = isset($options['hash']['decimal']) ?
            $options['hash']['decimal'] : '.';

        $thousands = isset($options['hash']['thousands']) ?
            $options['hash']['thousands'] : ',';

        return number_format($number, $places, $decimal, $thousands);
    },

    // Returns the number of posts based on options
    'post_count' => function($options) {
        // Convert status from CSV to array and trim whitespace
        if(is_string($options['hash']['status'])) {
            $options['hash']['status'] = array_map(
                'trim',
                explode(',', $options['hash']['status'])
            );
        }

        // See Post::count() for available options
        return \Postleaf\Post::count($options['hash']);
    },

    // Returns the correct string based on a number
    'plural' => function($value, $options = null) {
        // If only one argument was passed in, adjust options and set the default $value
        if(!$options) {
            $options = $value;
            $value = 0;
        }

        switch((int) $value) {
            case 0:
                $plural = $options['hash']['none'];
                break;
            case 1:
                $plural = $options['hash']['singular'];
                break;
            default:
                $plural = $options['hash']['plural'];
        }

        return str_replace('%', $value, $plural);
    },

    // Converts HTML to plain-text
    'text' => function($text) {
        return new \LightnCandy\SafeString(strip_tags($text));
    }

];