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
            } elseif ($options['hash']['date']) {
                $date = $options['hash']['date'];
            } else {
                // Fallback to the current date/time
                $date = date('Y-m-d H:i:s');
            }
        }

        // Determine format
        $format = isset($options['hash']['format']) ? $options['hash']['format'] : '%Y-%m-%d';

        return \Leafpub\Leafpub::strftime($format, strtotime($date));
    },

    // Compares two dates
    'date_compare' => function() {
        $args = func_get_args();
        $options = end($args);

        switch(count($args) - 1) {
            // One date
            case 1:
                $left = new \DateTime($args[0]);
                $operator = '==';
                $right = new \DateTime('now');
                break;

            // Two dates
            case 2:
                $left = new \DateTime($args[0]);
                $operator = '==';
                $right = new \DateTime($args[1]);
                break;

            // Two dates and an operator
            case 3:
                $left = new \DateTime($args[0]);
                $operator = $args[1];
                $right = new \DateTime($args[2]);
        }

        // Compare values
        switch(strtolower($operator)) {
            case '>':
                $compare = $left > $right;
                break;
            case '>=':
                $compare = $left >= $right;
                break;
            case '<':
                $compare = $left < $right;
                break;
            case '<=':
                $compare = $left <= $right;
                break;
                break;
            default:
                $compare = $left == $right;
                break;
        }

        if($compare) {
            return $options['fn']();
        } else {
            return $options['inverse'] ? $options['inverse']() : '';
        }
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
        $continue = $options['hash']['continue'];
        // Truncate it
        if($num_chars) {
            $string = \Leafpub\Leafpub::getChars($string, $num_chars ? $num_chars : 140);
        } else {
            // Return first n words
            $string = \Leafpub\Leafpub::getWords($string, $num_words ? $num_words : 50);
        }
        
        if ($continue){
            $string .= '... </br><a class="read-more" href="' .  \Leafpub\Models\Post::url($options['_this']['slug']) . '">' . \Leafpub\Language::term('read_more') . '</a>';
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
        return \Leafpub\Language::term($term, $options['hash']);
    },

    // Logs details to the screen
    'log' => function($var, $options = null) {
        return print_r($options ? $var  : $var['_this'], true);
    },

    // Convert markdown to HTML
    'markdown' => function($markdown, $options) {
        return \Leafpub\Leafpub::markdownToHtml($markdown);
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
        return \Leafpub\Models\Post::count($options['hash']);
    },

    'upload_count' => function($options){
        // See Upload::count() for available options
        return \Leafpub\Models\Upload::count($options['hash']);
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
    },
    
    // Fetch posts via parameter search
    'posts' => function($options){
        $posts = array();
        if (count($options['hash']) > 0){
            $availableOptions = array(
                'author' => null,
                'items_per_page' => 10,
                'page' => 1,
                'query' => null,
                'tag' => null,
                //'sort' => 'DESC' //NEWEST
            );
            $data = $options['hash'];
            foreach(array_keys($availableOptions) as $key){
                if (isset($data[$key])){
                    $searchFor[$key] = $data[$key];
                }
            }
            
            if (isset($data['featured'])){
                $searchFor['show_featured'] = true;
            }

            if (isset($data['count'])){
                $searchFor['items_per_page'] = $data['count'];
            }

            if (isset($data['sort'])){
                $sort = 'DESC';
                if (strtolower($data['sort']) == 'oldest'){
                    $sort = 'ASC';
                }
                $searchFor['sort'] = $sort;
            }
            $posts = \Leafpub\Models\Post::getMany($searchFor);
        }
        if(count($posts)) {
            return $options['fn'](['posts' => $posts]);
        } else {
            // No posts, do {{else}}
            return $options['inverse'] ? $options['inverse']() : '';
        }
    },
    
    'img' => function($image, $options){
        $possibleOptions = ['width', 'blur', 'brighten', 'sepia', 'emboss', 'grayscale'];
        if (isset($options['_this']['sign'])){
            $picData = $options['_this'];
        } else {
            $picData = \Leafpub\Models\Upload::getOne(\Leafpub\Leafpub::fileName($image));
        }
        $sign = $picData['sign'];
        $cpImage = $image = $picData['filename'] . '.' . $picData['extension'] ;
        
        if ($options){
            for($i = 0; $i < 6; $i++){
                if (isset($options['hash'][$possibleOptions[$i]])){
                    switch ($i){
                        case 0:
                            $width = 'width=' . $options['hash']['width'];
                            break;
                        case 1:
                        case 2:
                            $append .= '&' . $possibleOptions[$i] . '=' . $options['hash'][$possibleOptions[$i]];
                            break;
                        case 3:
                        case 4:
                        case 5:
                            $append .= '&' . $possibleOptions[$i];
                            break;
                    }
                }
            }
            $append .= '&sign=' . $sign;
            $appendix = ($width) ? $width . $append : substr($append, 1);
            $image .= '?' . $appendix;
        }
        
        if (isset($options['hash']['srcset'])){
            for ($i = 1; $i < 5; $i++){
                $widthP = $i*400;
                if ($width){
                    if ($widthP <= ceil($options['hash']['width'])){
                        $srcSet .= \Leafpub\Leafpub::url('/img/' . $cpImage . '?width=' . $widthP . $append . ' ' . $widthP . 'w,');
                    }
                } else {
                    $srcSet .= \Leafpub\Leafpub::url('/img/' . $cpImage . '?width=' . $widthP . $append . ' ' . $widthP . 'w,');
                }
            } 
            $append = ($width) ? $width . $append : substr($append, 1);
            $s = '<img src="' . \Leafpub\Leafpub::url('/img/' . $cpImage . '?' . $append) . '" alt="" srcset="' . $srcSet . '" />';   
            return new \LightnCandy\SafeString($s);
        } else {
            return \Leafpub\Leafpub::url('/img/' . $image);
        }
    }

];