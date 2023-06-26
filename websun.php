<?php

# Websun template parser by Mikhail Serov (1234ru at gmail.com)
# http://webew.ru/articles/3609.webew
# https://github.com/1234ru/websun/

/*

0.4.14 - $allowed_extensions fix for PHP 8.2

0.4.13 - fix of nested functions call regexp

0.4.12 - fix of nested functions call

0.4.11 - fix of handling * and \ in strings

0.4.10 - proper handling of * in string literals

0.4.9 - support of non-strict inequations (>= <=) added

0.4.8 - PHP 5.x compatibility fix

0.4.7 - var_value(): {*.1*} for root elements numeric keys

0.4.6 - negative cycle part: {%*var*}...{%!}here{%}

0.4.5 - {%**}{*:*}{%} bug fix

0.4.4 - htmlspecialchars() to allowed list + a bug fix

0.4.3 - boolean scalar values introduced

0.4.2 - brought lost PHP 5.3 support back

0.4.1 - default allowed callbacks list added

0.3.11 - spaces in {* var1 | var2 *} are allowed

0.3.10 - calling a template from itself using ^T notation: {* + *var* | ^T *}

0.3.00 - nested function call available (like {* @a( @b() ) *} )

0.2.12 - fix in var_value() to support empty strings for functions

0.2.11 - small fix in var_value(): checking for | (alternatives) before everything else

0.2.10 - multiline expressions for {* ... *} are supported (especially useful for calling functions with JSON arguments)

0.2.05 - var_value() correctly parses numeric literal values

0.2.04 - non-equality condition in ifs is supported

0.2.03 - @function(*array:^KEY*) fixed

0.2.02 - allowed spaces around comparison operator (requires to distinct a == b and a = =CONST) 

0.2.01 - comparison returns false when any of the operands is null (i.e. undefined variable)

0.2.00 - strict equality in if's implemented - {?*a==b*}...{?}

0.1.99 - some codestyle fixes by @KarelWintersky

0.1.98 - a bug fixed: allowed_extensions are passed to the child object

0.1.97 - removed closing tag at the end of the file

0.1.96 - function's call result may be used in the condition part of ifs

0.1.95 - fix in parse_cycle() - correctly parsing slash-containing keys (although their usage is against standards)

0.1.94 - object methods can be used as a callable functions (security check included).

0.1.93 - recursively parsing "else" part of if as well

0.1.92 - added xml to allowed template file extensions list

0.1.91 - added a negative part for ifs - {?!} 

0.1.90 - Short closing brackets for ifs and cycles! 
         {?} and {%} correspondingly.

0.1.84 - JSON arrays - [ ... ], { ... } - may be passed as an argument of functions
         (changes in parse_vars_templates_functions() and get_var_or_string() methods)

0.1.83 - functions call can handle round brackets, commas and vertical line
         inside of quoted literals now, like
         @function( "(|,)" )

0.1.82 - fixed check_if_condition_part regular expression, considering constants:
         to  =?[^*<>="]*+  from  [^*<>=]*+
         (side effect here: can't compare root array to variables
          like {*=varname*}, but it looks useless anyway)

0.1.81 - added object properties handling, like array keys

0.1.80 - allowed_extensions option implemented

0.1.71 - replaced too new array declaration [] with array() - keeping PHP 5.3 compatibility

0.1.70 - added :^N and :^i 

0.1.60 - __construct() accepts an array as of now 
         (wrapping function accepts distinct variables still)
       - "no_global_variables" setting implemented 

0.1.55 - fixed misbehavior of functions' args parsing 
         when dealing with comma inside of double quotes
         like @function(*var*, "string,string")
         
         По поводу массивов в JSON-нотации в качестве скалярных величин:
				// 1. Как *var* вытащить из json_decode?
				// Сначала *var* => json_encode(*var*, 1) 
				// (это будет голый массив данных; не-data-свойства потеряются)
				
				// 2. Как указывать кодировку (json_decode только в UTF-8),
				// $WEBSUN_CHARSET? 
				// 2а) Как вообще избавиться от %u0441 вместо кириллицы?
				// возможно только для UTF-8 или избирательно 1251,
				// т.к. нужно ловить буквы и делать им кодирование в %uXXXX
				// Всё это будет очень долго, особенно если это проводить
				// для каждой переменной 
				// (хотя там можно поставить костыль на [ и { - 
				// если не встретили в начале, то обрабатываем как обычно)
				

0.1.54 - $WEBSUN_ALLOWED_CALLBACKS instead of $WEBSUN_VISIBLES

0.1.53 - list of visible functions as a global variable for all versions
         (for a while - see v. 0.1.51)

0.1.53(beta) - added trim() at the beginning get_var_or_string()

0.1.52(beta) - using is_numeric() when parsing ifs (opposite to v.0.1.17) 

0.1.51(beta) - list of visible functions added (draft)
         prior to PHP 5.6 - as a global array variable
         PHP 5.6 and higher - as a namespace

0.1.50 - fixed default paths handling (see __construct() )

0.1.49 - added preg_quote() in parse_cycle() for searching array's name

0.1.48 - $ can be used in templates_root direcory path
         (^ makes no sense, because it means templates_root path itself) 

0.1.47 - called back the possibility of spaces before variables,
         because it makes parsing of cycles harder:
         instead of [*|&..]array: we must catch [*|&..]\s*array,
         which (according to one of the tests) works ~10% slower;
         simple (non-cycle) variables and constants 
         (literally how it is described in comment to previous version)
         can still work - this unofficial possibility will be left

0.1.46 - spaces can be like {?* a = b *}, {?* a > b *}
         (especially convenient when using constants: {?*a = =CONST*})

0.1.45 - now spaces can be around &, | and variable names in ifs

0.1.44 - loosen parse_cycle() regexp
         to accept arrays with non-alphanumeric keys
         (for example, it may occur handy to use 
         some non-latin string as a key),
         now {%* anything here *} is accepted.
         Note: except of dots in "anything", which are
         still interpreted as key separators.

0.1.43 - some fixes in profiling:
         parse_template's time is not added to total
         instead of this, total time is calculated
         as a time of highest level parse_template run
         Note: profiling by itself may increase
         total run time up to 60% 

0.1.42 - loosen regexp at check_if_condition_part()
         from [$=]?[\w.\^]*+ to [^*=<>]*+
         now any variable name can be used in ifs,
         not just alphanumeric;
         such loose behaviour looks more flexible
         and parallels var_value() algorithm
         (which can interpret any variable name
         except of with dots)

0.1.41 - fixed find_and_parse_cycle():
         "array_name:" prefixed with & is also catched 
         (required for ifs like {?*array:key1&array:key2*})

0.1.40 - fixed error in check_if_condition_part()
         (pattern)

0.1.39 - fixed error in check_if_condition_part()
         (forgotten vars like array^COUNT>1 etc.)

0.1.38 - fixed error in if alternatives (see 0.1.37)
         introduced check_if_condition_part() method
         added possibility of "AND", not just "OR" in if alternatives:
         {?*var_1="one"|var_2="two"*} it is OR {*var_1="one"|var_2="two"*?}
         {?*var_1="one"&var_2="two"*} it is AND {*var_1="one"&var_2="two"*?}
         
         Fixed profiling!
         Now any profiling information is passed to the higher level -
         otherwise (as it previously was) each object instance (i.e. each
         call of nested template or module) produced it's own and private
         timesheet.

0.1.37 - fixes in var_value():
         parsing of | (if alternatives) now goes before 
         parsing of strings ("..")
         so things like {?*var_1="one"|var_2="two"*} ... {*var_1="one"|var_2="two"*?}
         are possible
         (but things like {?*var="one|two"*} are not)

0.1.36 - correct string literals parsing - var_value() fix 
         (important when passed to module)
         

0.1.35 - two literals comparison is accepted in if's now
         (previously only variable was allowed at the left)
         Needed to do so because of :^KEY parsing - those
         are converted to literals BEFORE ifs are processed
         Substituted ("[^"*][\w.:$|\^]*+) subpattern 
         with the ("[^"*]+"|[\w.:$|\^]*+) one

0.1.34 - fixed surpassing variables to child templates
         (now {* + *var1|var2* | *tpl1|tpl2* *} works)

0.1.33 - fixed parse_if (now {?*var1|var2*}..{*var1|var2*?} works)

0.1.32 - fixed getting template name from variable
         (now variable can be like {*var1|var2|"string"*}
         as everywhere else)

0.1.31 - fixed /r/n deletion from the end of the pattern
         
0.1.30 - fixed parse_cycle() function:
         regexp now catches {* *%array:subarray* | ... *}
         (previously missed it)

0.1.29 - fixed a misprint

0.1.28 - A bunch of little fixes.
         
         Added profiling mode (experimental
         for a while so not recommended to use
         hardly).
         
         Regexp for ifs speeded up by substituting
         loose [^<>=]* with strict [\w.:$\^]*+
         (boosted the usecase about 100 times)
         
         In parse_cycle - also
         ([\w$.]*) instead of ([-\w$.]*)
         (don't remember, what "-" was for).
         
         Constants are now handled in var_value
         (with all other stuff like ^COUNT etc.)
         instead of in get_var_or_string().

0.1.27 - "alt" version is main now
         
         added {* + *some* | >{*var*} *} - 
         templates right inside of the var

0.1.26 - fixed some things with $matches dealing in parsee_if

0.1.25 - version suffix changed "beta" (b) to "alternative" (alt)

0.1.25 - fixed replacement of array:foo - 
         Now it is more strict and occurs if only 
         symbols *=<>| precede array:foo
         ( =<> - for {*array:foo_1>array:foo_2*}, |
           - for {*array:foo_1|array:foo_2*}, 
           * - for just {*array:foo*} )


0.1.24 - (beta) rewriting regexps with no named subpatterns 
         for compatibility with old PHP versions
         (PCRE documentation is unclear and issues like
         new PHP and no support of named subpatterns occur)
         EXCEPT pattern for addvars method
0.1.23 - fixed ^KEY parsing a little (removed preg_quote and fixed regexp)

0.1.22 - now not only *array:foo* (and *array:^KEY*) is caught,
         but array:foo and array:^KEY as well
         Needed it because otherwise if clauses like {*array:a|array:b*}
         are not parsed. This required substitution of str_replace with 
         with preg_replace which had no visible affect on perfomance.

0.1.21 - fixed some in var_value() (substr sometimes returns FALSE not empty string)
         everything is mb* now
         
0.1.20 - fixed trimming \r\n at the end  
         of the template 
0.1.19 - websun_parse_template_path() fixed
         to set current templates directory 
         to the one of template specified
         
0.1.18 - KEY added
0.1.17 - fixed some in var_value() 

теперь по умолчанию корневой каталог шаблона - 
тот, в котором выполняется вызвавший функцию скрипт

добавлены ^COUNT (0.1.13)
добавлены if'ы: {*var_1|var_2|"строка"|1234(число)*}
в if'ах добавлено сравнение с переменными:
{?*a>b*}..{*a>b?*}
{?*a>"строка"*}..{*a>"строка"*?}
{?*a>3*}..{*a>3*?}
*/

/**
 * Websun template parser by Mikhail Serov (1234ru at gmail.com)
 * http://webew.ru/articles/3609.webew
 * 2010-2019 (c)
 *
 * Class websun
 */
class websun {

	public $vars;
	public $templates_root_dir;
	public $templates_current_dir;
	public $TIMES;
	public $no_global_vars;
	public $current_template_filepath;
	private $profiling;
	private $predecessor; // объект шаблонизатора верхнего уровня, из которого делался вызов текущего

    private $allowed_extensions;
	
	private $default_allowed_callbacks = array( // Не константа для совместимости с PHP 5.5-
		'array_key_exists',
		'date',
		'DateTime::format',
		'htmlspecialchars',
		'implode',
		'in_array',
		'is_array',
		'is_null',
		'json_encode',
		'mb_lcfirst',
		'mb_ucfirst',
		'rand',
		'round',
		'strftime',
		'urldecode',
		'var_dump',
	);

	/**
	 * Конструктор класса шаблонизатора
	 *
	 * $options - ассоциативный массив с ключами:
	 * - data - данные
	 * - templates_root - корневой каталог шаблонизатора (рекомендуется указывать без закрываюшего слэша)
	 * - predecessor - объект-родитель (из которого вызывается дочерний)
	 * - allowed_extensions - список разрешенных расширений шаблонов (по умолчанию: *.tpl, *.html, *.css, *.js)
	 * - no_global_vars - разрешать ли использовать в шаблонах переменные глобальной области видимости
	 * - profiling - включать ли измерения скорости (пока не до конца отлажено)
	 *
	 * @param array $options[]
	 */
	function __construct($options) {
		$this->vars = $options['data'];
		
		if (isset($options['templates_root']) AND $options['templates_root']) // корневой каталог шаблонов
			$this->templates_root_dir = $this->template_real_path( rtrim($options['templates_root'], '/') );
		else { // если не указан, то принимается каталог файла, в котором вызван websun
			// С 0.50 - НЕ getcwd()! Т.к. текущий каталог - тот, откуда он запускается,
			// $this->templates_root_dir = getcwd();
			foreach (debug_backtrace() as $trace) {
				if (preg_match('/^websun_parse_template/', $trace['function'])) {
					$this->templates_root_dir = dirname($trace['file']);
					break;
				}
			}
			
			if (!$this->templates_root_dir) {
				foreach (debug_backtrace() as $trace) {
					if ($trace['class'] == 'websun') {
						$this->templates_root_dir = dirname($trace['file']);
						break;
					}
				}
			}
		}
		$this->templates_current_dir = $this->templates_root_dir . '/';
		
		$this->predecessor = (isset($options['predecessor']) ? $options['predecessor'] : FALSE);
		
		$this->allowed_extensions = (isset($options['allowed_extensions'])) 
			                       ? $options['allowed_extensions'] 
			                       : array( 'tpl', 'html', 'css', 'js', 'xml' );
		
		$this->no_global_vars = (isset($options['no_global_vars']) ? $options['no_global_vars'] : FALSE);
		
		$this->profiling = (isset($options['profiling']) ? $options['profiling'] : FALSE);
	}

	/**
	 * Парсит шаблон
	 *
	 * @param $template
	 * @return mixed
	 */
	function parse_template($template) {
		if ($this->profiling)
			$start = microtime(1);
		
		$template = preg_replace('/ \\/\* (.*?) \*\\/ /sx', '', $template); /**ПЕРЕПИСАТЬ ПО JEFFREY FRIEDL'У !!!**/

        $template = self::escapeChars($template);

		// С 0.1.51 отключили
		// $template = preg_replace_callback( // дописывающие модули
		// 	'/
		// 	{\*
		// 	&(\w+)
		// 	(?P<args>\([^*]*\))?
		// 	\*}
		// 	/x', 
		// 	array($this, 'addvars'), 
		// 	$template
		// 	);
		
		$template = $this->find_and_parse_cycle($template);
		
		$template = $this->find_and_parse_if($template);
		
		$template = preg_replace_callback( // переменные, шаблоны и функции
				'/
				{\*
				(
					(?:
						[^*]*+
						|
						\*(?!})
					)+
				)	
				\*}
				/x', 
				// до версии 0.2.10 в середине использовалось простое выражение (.*?)
				// оно не ловило многострочные выражения (хотя модификатор s мог это исправить)
				// и не содержало оптимизации с жадным квантификатором, который должен работать быстрее
				array($this, 'parse_vars_templates_functions'), 
				$template
			);

        $template = self::unescapeChars($template);

		if ($this->profiling AND !$this->predecessor) {
			$this->TIMES['_TOTAL'] = round(microtime(1) - $start, 4) . " s";
			// ksort($this->TIMES);
			echo '<pre>' . print_r($this->TIMES, 1) . '</pre>';
		}
		
		return $template;
	}
	
	// // дописывание массива переменных из шаблона
	// // (хак для Бурцева)
	// 0.1.51 - убрали; все равно Бурцев не пользуется
	// function addvars($matches) {
	// 	// if ($this->profiling) 
	// 	// 	// $start = microtime(1);
	// 	// 
	// 	// $module_name = 'module_'.$matches[1];
	// 	// # ДОБАВИТЬ КЛАССЫ ПОТОМ
	// 	// $args = (isset($matches['args'])) 
	// 	// 	// ? explode(',', mb_substr($matches['args'], 1, -1) ) // убираем скобки
	// 	// 	// : array();
	// 	// $this->vars = array_merge(
	// 	// 	// $this->vars, 
	// 	// 	// call_user_func_array($module_name, $args)
	// 	// 	// ); // call_user_func_array быстрее, чем call_user_func
	// 	// 
	// 	// if ($this->profiling) 
	// 	// 	// $this->write_time(__FUNCTION__, $start, microtime(1));
	// 	// 
	// 	// return TRUE;
	// }

	/**
	 *
	 * @param $string
	 * @return array|bool|int|mixed|null|string
	 */
	function var_value($string) {
		
		if ($this->profiling)
			$start = microtime(1);
		
		// можно делать if'ы:
		// {*var_1|var_2|"строка"|134*}
		// сработает первая часть, которая TRUE
		
		if (mb_strpos($string, '|') !== FALSE) {
			$f = __FUNCTION__;
			
			foreach (explode('|', $string) as $str) {
				// останавливаемся при первом же TRUE
				if ( $val = $this->$f(trim($str)) ) 
					break; 
			}
			
			$out = $val;
		}
		elseif (mb_substr($string, 0, 1) == '=') { # константа
			$C = mb_substr($string, 1);
			$out = (defined($C)) ? constant($C) : '';
		}
		
		elseif ( # скалярная величина
			mb_substr($string, 0, 1) == '"'
			AND 
			mb_substr($string, -1) == '"'
		) {
            $out = mb_substr($string, 1, -1);
            $out = self::unescapeChars($out);
        }
		elseif (is_numeric($string) AND substr($string, -1) != '.' AND substr($string, 0, 1) != '.')
			$out = $string + 0; // изящный способ преобразовать в число; http://php.net/manual/en/function.is-numeric.php#107326
			// 10-12.12.2019: Проверяем, что на конце не точка:
			// {%**}{*:*}{%} при числовых ключах разворачивается в
			// {*0.*}{*1.*}{*2.*}...
			// {*.1*} также воспринимается как число, что не логично -
			// если рассматривать по аналогии с {*.key*}
			// PHP считает строки "1." и ".1" числовыми

		elseif ($string == 'FALSE' OR $string == 'false')
			$out = FALSE;
		
		elseif ($string == 'TRUE' OR $string == 'TRUE')
			$out = TRUE;
		
		else {
			
			if (mb_substr($string, 0, 1) == '$') { 
				// глобальная переменная
				if (!$this->no_global_vars) {
					$string = mb_substr($string, 1);
					$value = $GLOBALS;
				}
				else
					$value = '';
			}
			else 
				$value = $this->vars;
				
			// допустимы выражения типа {*var^COUNT*}
			// (вернет count($var)) )
			if (mb_substr($string, -6) == '^COUNT') {
				$string = mb_substr($string, 0, -6);
				$return_mode = 'count';
			}
			else
				$return_mode = FALSE; // default
			
			$rawkeys = explode('.', $string);
			$keys = array();
			foreach ($rawkeys as $v) { 
				if ($v !== '') 
					$keys[] = $v; 
			}
			// array_filter() использовать не получается, 
			// т.к. числовой индекс 0 она тоже считает за FALSE и убирает
			// поэтому нужно сравнение с учетом типа
			
			// пустая строка указывает на корневой массив
			foreach($keys as $k) { 
				if (is_array($value) AND isset($value[$k])) 
					$value = $value[$k];
				
				elseif (is_object($value) AND property_exists($value, $k)) 
					$value = $value->$k;
				
				else {
					$value = NULL;
					break;
				}
			}
			
			// в зависимости от $return_mode действуем по-разному:
			$out = (!$return_mode)
				// возвращаем значение переменной (обычный случай)
				? $value
				
				// возвращаем число элементов в массиве
				: ( is_array($value) ? count($value) : FALSE )
				
				;
		}
		
		if ($this->profiling) 
			$this->write_time(__FUNCTION__, $start, microtime(1));
		
		return $out;
	}

	/**
	 * @param $template
	 * @return mixed
	 */
	function find_and_parse_cycle($template) {
		if ($this->profiling) 
			$start = microtime(1);
		// пришлось делать специальную функцию, чтобы реализовать рекурсию
		$out = preg_replace_callback(
			'/
			{ %\* ([^*]*) \* }
				( (?: [^{]* | (?R) | . )*? ) 
			(?: # "отрицательная" часть цикла
				{ %! }
				( (?: [^{]* | (?R) | . )*? )
			)?
			{ (?: % | \*\1\*% ) }
			/sx',
			array($this, 'parse_cycle'), 
			$template 
			);
			// инвертный класс - [^{]* - для быстрого совпадения
			// непрерывных цепочек статистически наиболее часто встречающихся символов 
		
		if ($this->profiling) 
			$this->write_time(__FUNCTION__, $start, microtime(1));
		
		return $out;
	}

	/**
	 * @param $matches
	 * @return bool|mixed|string
	 */
	function parse_cycle($matches) {
		
		if ($this->profiling) 
			$start = microtime(1);
		
		$array_name = $matches[1];
		$array = $this->var_value($array_name);
		
		$parsed = '';

		if ($array) {

			$dot = ($array_name != '' AND $array_name != '$')
				? '.'
				: '';

			$array_name_quoted = preg_quote($array_name);

			# Слэш - / - функция preg_quote не экранирует; т.к. мы используем его в качестве ограничителя для регулярных выражений, экранируем его самостоятельно
			$array_name_quoted = str_replace('/', '\/', $array_name_quoted); //

			$i = 0;
			$n = 1;
			foreach ($array as $key => $value) {
				$parsed .= preg_replace(
					array(// массив поиска
						"/(?<=[*=<>|&%])\s*$array_name_quoted\:\^KEY\b/",
						"/(?<=[*=<>|&%])\s*$array_name_quoted\:\^i\b/",
						"/(?<=[*=<>|&%])\s*$array_name_quoted\:\^N\b/",
						"/(?<=[*=<>|&%])\s*$array_name_quoted\:/"
					),
					array(// массив замены
						'"' . $key . '"',               // preg_quote для ключей нельзя,
						'"' . $i . '"',
						'"' . $n . '"',
						$array_name . $dot . $key . '.' // т.к. в них бывает удобно
					),                                 // хранить некоторые данные,
					$matches[2]                           // а preg_quote слишком многое экранирует
				);
				$i++;
				$n++;
			}
		}
		else { // возвращаем отрицательную часть цикла (с 10.12.2019)
			$parsed = (isset($matches[3])) ? $matches[3] : '';
		}

		$parsed = $this->find_and_parse_cycle($parsed);

		if ($this->profiling)
			$this->write_time(__FUNCTION__, $start, microtime(1));
		
		return $parsed;
	}

	/**
	 * @param $template
	 * @return mixed
	 */
	function find_and_parse_if($template) {
		
		if ($this->profiling)
			$start = microtime(1);
		
		$out = preg_replace_callback( 
				'/
				{ (\?\!?) \*  # открывающая "скобка"
				
				  (           # условие для проверки 
					(?:
						[^*]*+       # строгое выражение, никогда не возвращающееся назад;
						|            # буквально означает "любые символы, кроме звёздочки,
						\* (?! } )   # либо звёздочка, если только за ней сразу не следует
					)+               # закрывающая фигурная скобка
				   )     
				\*}      
				
				( (?: [^{]* | (?R) | . )*? ) # при положительном проверки результате (+ рекурсия)
				(?:
				  { \?\! } 
				  ( (?: [^{]* | (?R) | . )*? ) # при отрицательном результате проверки
				)? #  
				{ (?: \?  | \*\2\*\1 ) }     # закрывающая скобка
				/sx', 
				array($this, 'parse_if'), 
				$template
			); 
		 	// пояснения к рег. выражению см. в find_and_parse_cycle
		
		if ($this->profiling) 
			$this->write_time(__FUNCTION__, $start, microtime(1));
		
		return $out;
	}

	/**
	 * @param $matches
	 * @return mixed|string
	 */
	function parse_if($matches) {
		# 1 - ? или ?!
		# 2 - условие
		# 3 - при положительном результате 
		# 4 - при отрицательном результате (если указано)
		
		if ($this->profiling) 
			$start = microtime(1);
		
		$final_check = FALSE;
		
		$separator = (strpos($matches[2], '&'))
		           ? '&'  // "AND"
		           : '|'; // "OR"
		$parts = explode($separator, $matches[2]);
		$parts = array_map('trim', $parts); // убираем пробелы по краям
		
		$checks = array();
		
		foreach ($parts as $p) 
			$checks[] = $this->check_if_condition_part($p);
		
		if ($separator == '|') // режим "OR" 
			$final_check = in_array(TRUE, $checks);
		
		else // режим "AND"
			$final_check = !in_array(FALSE, $checks);
		
		$result = ($matches[1] == '?') 
			     ? $final_check 
			     : !$final_check ; 
		
		$parsed_if = ($result) 
			        ? $this->find_and_parse_if($matches[3]) 
			        : ( (isset($matches[4])) ? $this->find_and_parse_if($matches[4]) : '' ) ;
		
		if ($this->profiling) 
			$this->write_time(__FUNCTION__, $start, microtime(1));
		
		return $parsed_if;
	}

	/**
	 *
	 * @param $str
	 * @return bool
	 */
	function check_if_condition_part($str) {
		
		if ($this->profiling)
			$start = microtime(1);
		
		preg_match(
				'/^
				   (  
				   	"[^"*]*"     # строковый литерал
				   	
				   	|            # или
				   	
				   	=?[^!<>=]*+   # имя константы или переменной или вызов функции
				   )  
				   
					(?: # если есть сравнение с чем-то:
						\s*
						(!?==?|<=?|>=?)  # знак сравнения 
						\s*
						(.*)     # то, с чем сравнивают
					)?
					
					$
				/x',
				$str,
				$matches
			);
		
		$left = ( strpos(trim($matches[1]), '@') === 0 ) 
		      ? $this->parse_vars_templates_functions( array( 1 => $matches[1] ) ) # вызов функции
			  : $this->var_value(trim($matches[1])) ;
		
		if ( is_null($left) ) // если в сравнении участвует переменная, которая не определена, в любом случае возвращаем FALSE
			$check = FALSE;
		
		else {
		
			if (!isset($matches[2]))
				$check = ($left == TRUE);
			
			else {
				
				if (isset($matches[3]))
					$right = ( strpos(trim($matches[3]), '@') === 0 ) 
						   ? $this->parse_vars_templates_functions( array( 1 => $matches[3] ) ) # вызов функции
						   : $this->var_value(trim($matches[3]));
				else
					$right = FALSE ;
				
				if ( is_null($right) )
					$check = FALSE;
				else 
					switch($matches[2]) {
						case '=': $check = ($left == $right); break;
						case '!=': $check = ($left != $right); break;
						case '==': $check = ($left === $right); break;
						case '!==': $check = ($left !== $right); break;
						case '>': $check = ($left > $right); break;
                        case '>=': $check = ($left >= $right); break;
                        case '<': $check = ($left < $right); break;
                        case '<=': $check = ($left <= $right); break;
						default: $check = ($left == TRUE);
					}
			}
		}
		
		if ($this->profiling) 
			$this->write_time(__FUNCTION__, $start, microtime(1));
		
		return $check;
	}

	/**
	 *
	 * @param $matches
	 * @return mixed|string
	 */
	function parse_vars_templates_functions($matches) {
		if ($this->profiling) 
			$start = microtime(1);
		
		// тут обрабатываем сразу всё - и переменные, и шаблоны, и функции
		$work = $matches[1]; // ВНИМАНИЕ! При смене номера подмаски исправить также случаи в check_if_condition_part, где обрабатывается вызов функции (в двух местах)
		
		$work = trim($work); // убираем пробелы по краям
		
		if (mb_substr($work, 0, 1) == '@') { // функции {* @name(arg1,arg2) | template *}
		
			$p = '/
				^
				( # 1 - вызов функции 
					@ 
					( [^(]++ ) # 2 - имя функции
					\( 
					( # 3 - аргументы 
						(?: 
							[^@()"]++  
							| 
							"[^"]*+"
							|
							(?1)
							# \s*+ ( (?1) (?:\s*+,\s*+)? )+ \s*+
								# рекурсивным на весь шаблон (?R) это выражение делать нельзя,
								# т.к. здесь есть еще часть, отвечающая за подключение шаблона
						)* 
					) 
					\) 
				)
				(?: 
					\s*+ 
					\| 
					\s*+ (.++) # 4 - вызов шаблона 
				)? 
				$
				/x';
				// выражение неплохо оптимизировано: захватывающие квантификаторы 
				// ("+" - не возвращаться назад) - и пр.,
				// однако сам по себе вызов функций является довольно короткой строкой,
				// так что хорошо работать будет любое выражение
				
			if (preg_match( $p, $work, $m) ) {
				
				$function_string = trim($m[2]);
				
				// Возможны три варианта имени функции:
				// самый простой: литерал
				// переменная: *var*
				// статический метод: Class::function
				// свойство объекта: *var*->fn
				
				preg_match('/^\*([^*]++)\*(?:->(\w+))?$/', $function_string, $w); // просто $w
				
				$call; // Сюда запишем некоторые параметры того, что будем вызывать
				       // Вообще не круто использовать наряду с $callback (см. ниже),
					   // но лучше ничего не придумалось.
					   
				if (!$w) { // "звёздочек" нет - простая функция или статический метод
				
					$tmp = explode('::', $function_string);
					
					if (count($tmp) == 1)
						$call = array(
							'function' => $function_string, 
							'for_check' => $function_string 
						);
					
					else
						$call = array( 
							'class' => $tmp[0],
							'method' => $tmp[1], 
							'for_check' => "$tmp[0]::$tmp[1]" 
						);
				}
				else { // "звёздочки" есть - нужно получить из переменной
					   // т.к. точно знаем, что переменная, а не литерал,
					   // вызываем сразу var_value, минуя get_var_or_string
					$var = $this->var_value($w[1]);
					
					if (!isset($w[2])) // простая функция
						$call = array( 'function' => $var, 'for_check' => $var );
					
					else	// метод объекта
						$call = array( 
							'object' => $var,
							'method' => $w[2], 
							'for_check' => get_class($var) . "::$w[2]"
						);
						
					unset($var);
					
				}
				unset($w);
				
				// if (PHP_VERSION_ID / 100 > 506) { // это включим позже, получим PHP 5.6
					// $list = - тут ссылка на константу из namespace
				// else
					global $WEBSUN_ALLOWED_CALLBACKS;
					$list = array_unique( array_merge(
						$this->default_allowed_callbacks,
						isset($WEBSUN_ALLOWED_CALLBACKS) ? $WEBSUN_ALLOWED_CALLBACKS : []
					) );
				// }
				
				if ($list and in_array($call['for_check'], $list) )
					$allowed = TRUE;
				else {
					$allowed = FALSE;
					trigger_error("'$call[for_check]()' is not in the list of allowed callbacks.", E_USER_WARNING);
				}
				
				if ($allowed) {
					
					$args = array();
					
					if (isset($m[3])) {
						
						preg_match_all('
							/ 
								# выражение составлено так, что в каждой подмаске
								# должен совпасть хотя бы один символ 
								# v. 0.2.03: сначала ловим строки вида *"..."*, которые остаются от подстановки *:^KEY* 
								\*"[^"]*+"\* 
								|
								( @ [^(]++ \( \s*+ (?: (?R) \s*+(?:,\s*+(?R))*+ )? \) ) # вложенные вызовы функций (v. 0.3.0) 
									# Этот подшаблон обязательно должен идти перед следующим, т.к. иначе там будет захвачено имя функции
									# пробелы и запятые указаны в явном виде, т.к. нигде больше в шаблоне они не встречаются и он с ними не совпадает
									# если их так не указать, участок рекурсивного совпадения
									# будет неправильно фрагментирован.
									# Случай, когда содержимое скобок пусто (не переданы аргументы),
									# также нужно описывать в явном виде, поскольку строка
									# , начинающаяся с пробела, с данным шаблоном не совпадает
								|
								[^ \s,"{\[@() ]++ # переменные, константы или числа (ведущий пробел тоже исключаем) 
								|
								"[^"]*+" # строки
								|
								( \[ (?: [^\[\]]*+ | (?2) )* \] ) # JSON: обычные массивы (с числовыми ключами)
								|
								( { (?: [^{}]*+ | (?3) )* } ) # JSON: ассоциативные массивы
								
							/x', 
							$m[3], 
							$tmp
						);
						
						if ($tmp) 
							$args = array_map( array($this, 'get_var_or_string'), $tmp[0] );
						
						unset($tmp);
					}
					
					if (isset($call['function']))
						$callback = $call['function'];
					
					else {
						
						if (isset($call['class']))
							$callback[] = $call['class'];
						else
							$callback[] = $call['object'];
						
						$callback[] = $call['method'];
					}
					
					$subvars = call_user_func_array($callback, $args);
					
					if ( isset($m[4]) )  // передали указание на шаблон
						$html = $this->call_template($m[4], $subvars);
					
					else 
						$html = $subvars; // шаблон не указан => функция возвращает строку
				}
				else
					$html = '';
			}
			else 
				$html = ''; // вызов функции сделан некорректно
		}
		elseif (mb_substr($work, 0, 1) == '+') { 
			// шаблон - {* +*vars_var*|*tpl_var* *}
			// переменная как шаблон - {* +*var* | >*template_inside* *}
			$html = '';
			$parts = preg_split(
					'/(?<=[\*\s])\|(?=[\*\s])/', // вертикальная черта
					mb_substr($work, 1) // должна ловиться только как разделитель
					// между переменной и шаблоном, но не должна ловиться 
					// как разделитель внутри нотации переменой или шаблона
					// (например, {* + *var1|$GLOBAL* | *tpl1|tpl2* *}
				); 
			$parts = array_map('trim', $parts); // убираем пробелы по краям
			if ( !isset($parts[1]) ) { 	// если нет разделителя (|) - значит, 
							                  // передали только имя шаблона +template
							                  
				$html = $this->call_template($parts[0], $this->vars);
			}
			else {
				$varname_string = mb_substr($parts[0], 1, -1); // убираем звездочки
				// {* +*vars* | шаблон *} - простая передача переменной шаблону
				// {* +*?vars* | шаблон *} - подключение шаблона только в случае, если vars == TRUE
				// {* +*%vars* | шаблон *} - подключение шаблона не для самого vars, а для каждого его дочернего элемента 
				$indicator = mb_substr($varname_string, 0, 1);
				if ($indicator == '?') { 
					if ( $subvars = $this->var_value( mb_substr($varname_string, 1) ) )
						// 0.1.27 $html = $this->parse_child_template($tplname, $subvars);
						$html = $this->call_template($parts[1], $subvars);
				}
				elseif ($indicator == '%') {
					if ( $subvars = $this->var_value( mb_substr($varname_string, 1) ) ) {
						foreach ( $subvars as $row ) { 
							// 0.1.27 $html .= $this->parse_child_template($tplname, $row);
							$html .= $this->call_template($parts[1], $row);
						}
					}
				}
				else {
					$subvars = $this->var_value( $varname_string );
					// 0.1.27 $html = $this->parse_child_template($tplname, $subvars);
					$html = $this->call_template($parts[1], $subvars);
				}
			}
		}
		else 
			$html = $this->var_value($work); // переменная (+ константы - тут же)
			
		if ($this->profiling) 
			$this->write_time(__FUNCTION__, $start, microtime(1));

		return $html;
	}

	function parse_function($str) {
		
		
		
	}

	/**
	 * @param $template_notation
	 * @param $vars
	 * @return mixed
	 */
	function call_template($template_notation, $vars) {
		if ($this->profiling) 
			$start = microtime(1);

		// $template_notation - либо путь к шаблону,
		// либо переменная, содержащая путь к шаблону,
		// либо шаблон прямо в переменной - если >*var*

		/**
		 * @var websun $c;
		 */
		$c = __CLASS__; // нужен объект этого же класса - делаем

		/**
		 * @var websun $subclass;
		 */
		$subobject = new $c(array(
				'data' => $vars, 
				'templates_root' => $this->templates_root_dir,
				'predecessor' => $this,
				'no_global_vars' => $this->no_global_vars,
				'allowed_extensions' => $this->allowed_extensions, 
				// 'profiling' => $this->profiling,
			));

		$template_notation = trim($template_notation);
		
		if (mb_substr($template_notation, 0, 1) == '>') { 
			// шаблон прямо в переменной
			$v = mb_substr($template_notation, 1);
			$subtemplate = $this->get_var_or_string($v);
			$subobject->templates_current_dir = $this->templates_current_dir;
		}
		else {
			$path = ($template_notation === '^T') // рекурсивный вызов шаблона
				? $this->current_template_filepath
				: $this->get_var_or_string($template_notation);
			$subobject->templates_current_dir = pathinfo($this->template_real_path($path), PATHINFO_DIRNAME ) . '/';
			$subobject->current_template_filepath = $path;
			$subtemplate = $this->get_template($path);
		}
		
		$result = $subobject->parse_template($subtemplate);
		
		if ($this->profiling) 
			$this->write_time(__FUNCTION__, $start, microtime(1));
		
		return $result;
	}

	/**
	 *
	 * @param $str
	 * @return array|bool|int|mixed|null|string
	 */
	function get_var_or_string($str) {
		// используется, в основном, для получения имён шаблонов и функций
		
		$str = trim($str);
		
		if ($this->profiling) 
			$start = microtime(1);
		
		$first_char = mb_substr($str, 0, 1);
		
		if ($first_char == '*') // если вокруг есть звездочки - значит, перменная или константа
			$out = $this->var_value( mb_substr($str, 1, -1) ); 
			
		elseif ($first_char == '[' OR $first_char == '{') { // JSON
            $out = json_decode(self::unescapeChars($str), TRUE);
            $json_decode_status = json_last_error();
			if ($json_decode_status !== JSON_ERROR_NONE)
				trigger_error("Error (code = $json_decode_status) parsing JSON array literal $str", E_USER_WARNING);
		}
		
		elseif ($first_char == '@')  // в виде аргумента передан вызов функции
			$out = $this->parse_vars_templates_functions( array( 1 => $str ) );
			// Выделить обработку функций в отдельный метод затруднительно:
			// после работы такого метода нужно также знать, относится ли функция к разрешённым;
			// ни возвращать статус функции вместе с результатом её работы,
			// ни заводить специальную переменную состояния для хранения allowed
			// удобным не выглядит, поэтому пока оставляем так.
		
		else // нет звездочек - значит, скалярный литерал
			$out = ($first_char == '"') 
			     ? mb_substr($str, 1, -1) // в двойных кавычках - строка
			     : $str ;  // число
		
		if ($this->profiling) 
			$this->write_time(__FUNCTION__, $start, microtime(1));
		
		return $out;
	}

	/**
	 *
	 * @param $tpl
	 * @return bool|mixed|string
	 */
	function get_template($tpl) {
		if ($this->profiling) 
			$start = microtime(1);
		
		if (!$tpl) return FALSE;
		
		$tpl_real_path = $this->template_real_path($tpl);
		
		$ext = pathinfo($tpl_real_path, PATHINFO_EXTENSION);
		
		if (!in_array($ext, $this->allowed_extensions)) {
			trigger_error(
				"Template's <b>$tpl_real_path</b> extension is not in the allowed list (" 
				 . implode(", ", $this->allowed_extensions) . "). 
				 Check <b>allowed_extensions</b> option.", 
				E_USER_WARNING
			);
			return '';
		}
		
		// return rtrim(file_get_contents($tpl_real_path), "\r\n");

		// (убираем перенос строки, присутствующий в конце любого файла)
		$out = preg_replace(
				'/\r?\n$/',
				'',
				file_get_contents($tpl_real_path)
			);
		
		if ($this->profiling) 
			$this->write_time(__FUNCTION__, $start, microtime(1));
		
		return $out;
	}

	/**
	 * Функция определяет реальный путь к шаблону в файловой системе
	 * первый символ пути к шаблону определяет тип пути
	 * если в начале адреса есть / - интерпретируем как абсолютный путь ФС
	 * если второй символ пути - двоеточие (путь вида C:/ - Windows) - также интепретируем как абсолютный путь ФС
	 * если есть ^ - отталкиваемся от $templates_root_dir
	 * если $ - от $_SERVER[DOCUMENT_ROOT]
	 * во всех остальных случаях отталкиваемся от каталога текущего шаблона - templates_current_dir
	 *
	 * @param $tpl
	 * @return string
	 */
	function template_real_path($tpl) {
		if ($this->profiling)
			$start = microtime(1);
		
		$dir_indicator = mb_substr($tpl, 0, 1);
		
		$adjust_tpl_path = TRUE;
		
		if ($dir_indicator == '^') $dir = $this->templates_root_dir;
		elseif ($dir_indicator == '$') $dir = $_SERVER['DOCUMENT_ROOT'];
		elseif ($dir_indicator == '/') { $dir = ''; $adjust_tpl_path = FALSE; } // абсолютный путь для ФС 
		else {
			if (mb_substr($tpl, 1, 1) == ':') // Windows - указан абсолютный путь - вида С:/...
				$dir = '';
			else  
				$dir = $this->templates_current_dir;  
			
			$adjust_tpl_path = FALSE; // в обоих случаях строку к пути менять не надо
		}
		
		if ($adjust_tpl_path) $tpl = mb_substr($tpl, 1);
		
		$tpl_real_path = $dir . $tpl;
		
		if ($this->profiling) 
			$this->write_time(__FUNCTION__, $start, microtime(1));
		
		return $tpl_real_path;
	}

	/**
	 * @param $method
	 * @param $start
	 * @param $end
	 */
	function write_time($method, $start, $end) {
		//echo ($this->predecessor) . '<br>';

		if (!$this->predecessor)
			$time = &$this->TIMES;
		
		else
			$time = &$this->predecessor->TIMES ;
		
		if (!isset($time[$method]))
			$time[$method] = array(
					'n' => 0,
					'last' => 0,
					'total' => 0,
					'avg' => 0
				);
			
		$time[$method]['n'] += 1;
		$time[$method]['last'] = round($end - $start, 4);
		$time[$method]['total'] += $time[$method]['last'];
		$time[$method]['avg'] = round($time[$method]['total'] / $time[$method]['n'], 4) ;
	}

    private const CHARS_TO_REPLACE  = [ '\*',   '\\\\' ]; // обязательно сначала звездочка, потом слэш,
    private const CHARS_TO_GET_BACK = [ '*',    '\\\\' ]; // иначе будет ошибка при обратной замене
    private const CHARS_REPLACEMENT = [ "\x01", "\x02" ];

    private static function escapeChars($str)
    {
        // Временно заменяем двойные слэшии и экранированные звездочки
        // непечатаемыемыми символами,
        // которые не могли встретиться в шаблоне.
        return str_replace(
            self::CHARS_TO_REPLACE,
            self::CHARS_REPLACEMENT,
            $str
        );
    }

    private static function unescapeChars($str)
    {
        return str_replace(
            self::CHARS_REPLACEMENT,
            self::CHARS_TO_GET_BACK,
            $str
        );
    }




} // end class


/**
 * Функция-обёртка для быстрого вызова класса.
 * принимает шаблон в виде пути к нему
 *
 * @param $data
 * @param $template_path
 * @param bool|FALSE $templates_root_dir
 * @param bool|FALSE $no_global_vars
 * @return mixed
 */
function websun_parse_template_path(
		$data, 
		$template_path, 
		$templates_root_dir = FALSE,
		$no_global_vars = FALSE
		// $profiling = FALSE - пока убрали
	) {
	$W = new websun(array(
		'data' => $data, 
		'templates_root' => $templates_root_dir,
		'no_global_vars' => $no_global_vars,
	));
	$tpl = $W->get_template($template_path);
	$W->current_template_filepath = $template_path;
	$W->templates_current_dir = pathinfo( $W->template_real_path($template_path), PATHINFO_DIRNAME ) . '/';
	$string = $W->parse_template($tpl);
	return $string;
}

/**
 * Функция-обёртка для быстрого вызова класса
 * принимает шаблон непосредственно в виде кода
 *
 * @param $data
 * @param $template_code
 * @param bool|FALSE $templates_root_dir
 * @param bool|FALSE $no_global_vars
 * @return mixed
 */
function websun_parse_template(
		$data, 
		$template_code, 
		$templates_root_dir = FALSE,
		$no_global_vars = FALSE
		// profiling пока убрали
	) {
	$W = new websun(array(
		'data' => $data, 
		'templates_root' => $templates_root_dir,
		'no_global_vars' => $no_global_vars 
	));
	$string = $W->parse_template($template_code);
	return $string;
}
