<?php

/**
 * Class Paging
 * *** strankovaci peklo ***
 * *** soubor muzete libovolne modifikovat a sirit dal, jen prosim ponechte v kodu tuto hlavicku ***
 * @copyright 2008 Michal Sobola <msobola@seznam.cz> Luke Skywalker a Fox Mulder
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 * @version 1.1.3
 * @revision 2010-03-04
 **/

/**
 * ~ changelog from 1.0
 * 1.0.1 - fixed bug 'Call-time pass-by-reference has been deprecated on line 181'
 * 1.1.0 - fixed bug s generovanim ? kdyz to uz ? obsahuje
 *       - nahrada vsech array_key_exists za isset + intval za pretypovani
 *       - pridana metoda toString
 *       - odstranena zapovezena funkce e reg_ replace
 * 1.1.1 - pitomosti
 * 1.1.2 - do html doplneny atribut rel
 * 1.1.3 - fixed roztodivny bug
 **/

/**
 * nutne rezervovat promennou $_GET['page']
 *
 * strankovani ma 4 mozne vystupy, zalezi na nastaveni paging_mode a output_mode
 * SQL dotaz pro spocitani zaznamu je umyslne mimo knihovnu, aby nebyla zavisla i na pripadne databazove tride
 * jedine, co je povinne je konstrukt s dvema argumenty a metody set_paging() + export_paging()
 * namisto export_paging() je mozne vyuzit i get_paging(), coz vrati primo pole - pro vlastni zpusob vytisknuti v sablone
 * defaultne vraci knihovna primy html kod, ktery je nastavitelny pomoci nepovinne metody set_html_patterns
 *
 * moznosti vystupu:
 * 1 | 2 | 3 | 4 | 5            (paging_mode = 0 | output_mode = 0)
 * 1-10 | 11-20 | 21-30 | 31-32 (paging_mode = 0 | output_mode = 1)
 * <<  <  5  >  >>              (paging_mode = 1 | output_mode = 0)
 * 1 | 2 | 3 ... 50             (paging_mode = 1 | output_mode = 1)
 *
 * priklad nasazeni:
 **/

/**

    $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `tabulka`"), 0);

    // celkem | zakladna | [po kolika] | [format parametru]
    $paging = new Paging($total, 'http://www.treba.cz/kategorie/', 20, '&amp;page=%s');
    $paging->set_title_format('Stránka %s');
    $paging->set_around(2);
    $paging->set_paging_mode(1); // 0
    $paging->set_output_mode(1); // 0
    $paging->set_paging();

    // vystup
    echo $paging->export_paging(); || echo $paging;

 **/
class Paging {

	/**
	 * zakladna pro generovani odkazu
	 * @var string
	 **/
	private $base;

	/**
	 * kolik je prispevku celkem
	 * @var int
	 **/
	private $total;

	/**
	 * start pro sql dotaz
	 * @var int
	 **/
	private $start;

	/**
	 * po kolika strankujeme, taktez limit pro sql dotaz
	 * @var int
	 **/
	private $limit = 10;

	/**
	 * aktualni stranka, get promenna
	 * @var int
	 **/
	private $actual = 0;

	/**
	 * kolik okolnich odkazu zobrazujeme - pro metodu advanced_paging
	 * @var int
	 **/
	private $around = 5;

	/**
	 * format odkazu
	 * @var string
	 **/
	private $href_format = '?page=%s';

	/**
	 * format titulku
	 * @var string
	 **/
	private $title_format = 'Stránka %s';

	/**
	 * typ strankovani (0 = normalni, 1 = pokrocile)
	 * @var enum(0,1)
	 **/
	private $paging_mode = 0;

	/**
	 * typ vystupu (0 = prvni typ, 1 = druhy typ)
	 * @var enum(0,1)
	 **/
	private $output_mode = 0;

	/**
	 * html pattern - co poleze na vystup
	 * zakladni nastaveni vystupu
	 * @var array
	 **/
	private $pattern = array('active' => '<strong>%s</strong>',
			// klasicke strankovani, aktivni polozka
			'link' => '<a href="%s" title="%s" rel="%s">%s</a>',
			// format odkazu
			'separator' => ' <span>|</span> ', // oddelovac
			'dots' => ' ... ', // 3 tecky pro vystup ve tvaru  1 | 2 | 3 ...
			'dash' => '-',
			// pomlcka, pro strankovani ve tvaru 1-10 | 11-20
			'marks' => array( // pole pro vystup ve tvaru  <<  <  3 | 4 | 5  >  >>
					'laquo' => '&laquo;', 'raquo' => '&raquo;', 'lt' => '&lt;',
					'gt' => '&gt;',),);

	/**
	 * vystup strankovani
	 * @var array
	 **/
	public $paging = array();

	/**
	 * aktualni stranka
	 * @var array
	 **/
	public $actual_page = array();

	/**
	 * prvni stranka
	 * @var array
	 **/
	public $first_page = array();

	/**
	 * posledni stranka
	 * @var array
	 **/
	public $last_page = array();

	/**
	 * Konstrukt
	 * nastavi vsechny potrebne parametry pro strankovani - argumenty jsou v poradi dle dulezitosti
	 * jedina povinna polozka je celkovy pocet zaznamu, zbytek si knihovna umi odvodit sama
	 * vsechny argumenty jsou vselijak osetrovane, neni nutne komentovat
	 * @param int $total - kolik polozek celkem mame
	 * @param string [$base] - zakladna pro odkazy ve tvar http://www.treba.cz/kategorie/, ne pouze base url webu - pro jistotu doporucuji uvadet
	 * @param int [$limit] - po kolika strankujeme, vychozi 10
	 * @param string [$href_format] - format odkazu, vychozi ?page=%s
	 **/
	public function __construct($total, $base = null, $limit = 0,
			$href_format = null) {
		$this->total = abs((int) $total);
		if (!$total) {
			return false;
		}

		$this->base = (string) $base;
		if (!$base) {
			$this->base = preg_replace('/[&|\?]page=([0-9]*)/', '',
					$_SERVER['REQUEST_URI']);
		}

		$limit = (int) $limit;
		if ($limit) {
			$this->limit = $limit;
		}

		$href_format = (string) $href_format;
		if ($href_format) {
			if (strpos($href_format, '%s')) {
				$this->href_format = $href_format;
			}
		} else {
			if (strpos($_SERVER['REQUEST_URI'], '?')
					|| strpos($_SERVER['REQUEST_URI'], '&')) {
				$this->href_format = '&amp;page=%s';
			}
		}

		$p = (!empty($_GET['page']) ? (int) $_GET['page'] : 0);

		$this->actual = abs($p) - 1;
		if ($this->actual < 0) {
			$this->actual = 0;
		}

		if (($this->actual * $this->limit) >= $this->total) {
			$this->actual = 0;
			$this->start = 0;
		} else {
			$this->start = $this->actual * $this->limit;
		}
	}

	/**
	 * nastavi typ strankovani
	 * @param int [$paging_mode]
	 **/
	public function set_paging_mode($paging_mode = 0) {
		if ($paging_mode) {
			$this->paging_mode = 1;
		}
	}

	/**
	 * nastavi typ exportu
	 * @param int [$output_mode]
	 **/
	public function set_output_mode($output_mode = 0) {
		if ($output_mode) {
			$this->output_mode = 1;
		}
	}

	/**
	 * set_title_format
	 * nastavi format titulku, kt. budeme generovat odkazum - retezec musi obsahovat %s pro sprintf
	 * @param string [$title_format]
	 **/
	public function set_title_format($title_format = null) {
		if (is_string($title_format) && strpos($title_format, '%s') !== false) {
			$this->title_format = $title_format;
		}
	}

	/**
	 * set_around
	 * nastavi kolik zaznamu okolo budeme zobrazovat u pokrocileho strankovani
	 * @param int [$around]
	 **/
	public function set_around($around = 0) {
		$around = (int) $around;
		if ($around) {
			$this->around = $around;
		}
	}

	/**
	 * set_html_patterns
	 * nastavi vsechby potrebe vzory pro generovani html vystupu
	 * pole $marks musi mit klice 'laquo','raquo','lt' a 'gt'
	 * @param string [$active]
	 * @param string [$link]
	 * @param string [$separator]
	 * @param string [$dots] (pokrocile strankovani)
	 * @param string [$dash]
	 * @param array [$marks] (pokrocile strankovani - druha metoda vypisu)
	 **/
	public function set_html_patterns($active = null, $link = null,
			$separator = null, $dots = null, $dash = null,
			array $marks = array()) {
		if ($active) {
			$this->pattern['active'] = $active;
		}
		if ($link) {
			$this->pattern['link'] = $link;
		}
		if ($separator) {
			$this->pattern['separator'] = $separator;
		}
		if ($dots) {
			$this->pattern['dots'] = $dots;
		}
		if ($dash) {
			$this->pattern['dash'] = $dash;
		}
		if (is_array($marks) && $marks) {
			if (isset($marks['laquo'])) {
				$this->pattern['marks']['laquo'] = $marks['laquo'];
			}
			if (isset($marks['raquo'])) {
				$this->pattern['marks']['raquo'] = $marks['raquo'];
			}
			if (isset($marks['lt'])) {
				$this->pattern['marks']['lt'] = $marks['lt'];
			}
			if (isset($marks['gt'])) {
				$this->pattern['marks']['gt'] = $marks['gt'];
			}
		}
	}

	/**
	 * create_array_fields
	 * vytvori polozky v poli pro kazdy zaznam
	 * @param int $i icko z foru
	 * @return array
	 **/
	private function create_array_fields($i) {
		return array('key' => $i, // klic ke strance
				'page' => $i + 1,
				// dana stranka
				'href' => htmlspecialchars($this->base
						. ($i > 0 ? sprintf($this->href_format, ($i + 1)) : '')),
				// odkaz na danou stranku
				'title' => sprintf($this->title_format, ($i + 1)),
				// titulek
				'from' => ($i * $this->limit) + 1,
				// od kolikateho prispevku zobrazujeme
				'to' => ($i * $this->limit + $this->limit < $this->total ? $i
								* $this->limit + $this->limit : $this->total),
				// do kolikateho prispevku zobrazujeme - jsme-li na konci, priradime akorat $this->total
				'actual' => ($i == $this->actual ? 1 : 0), // jsme tam nebo ne
		);
	}

	/**
	 * nastavi strankovani
	 * 0 = normalni strankovani, vychozi
	 * 1 = pokrocile strankovani
	 **/
	public function set_paging() {
		if ($this->total > $this->limit) {
			if ($this->paging_mode == 1) {
				if ($this->actual * $this->limit > $this->total) {
					$this->actual = ceil($this->total / $this->limit) - 1;
				}

				$this->paging = array('prev' => array(), 'next' => array());

				// predchazejici
				for ($i = $this->actual - 1; $i
						>= $this->actual - $this->around && $i >= 0; $i--) {
					$this->paging['prev'][] = $this->create_array_fields($i);
				}

				sort($this->paging['prev']);

				// nasledujici
				for ($i = $this->actual + 1; $i
						<= $this->actual + $this->around
						&& $i <= ceil($this->total / $this->limit) - 1; $i++) {
					$this->paging['next'][] = $this->create_array_fields($i);
				}

				$this->actual_page = $this->create_array_fields($this->actual); // o aktualni strance
				$this->first_page = $this->create_array_fields(0); // musime explicitne priradit hodnoty o prvni strance
				$this->last_page = $this
						->create_array_fields(
								(ceil($this->total / $this->limit) - 1)); // o posledni strance
			} else {
				$for_limit = ceil($this->total / $this->limit) - 1;

				if ($this->actual * $this->limit > $this->total) {
					$this->actual = $for_limit;
				}

				for ($i = 0; $i <= $for_limit; $i++) {
					$this->paging[] = $this->create_array_fields($i);
				}

				$this->actual_page = $this->paging[$this->actual];
				$this->first_page = $this->paging[0];
				$this->last_page = $this->paging[$for_limit];
			}
		}
	}

	/**
	 * export_paging
	 * vrati html kod strankovani
	 * podle $output_mode vybere jednu ze dvou moznosti (0 => prvni, else druha)
	 * exportuje typ strankovani podle toho, jake bylo nastavnene - kazde ma jednu ze dvou moznosti
	 * @return string
	 **/
	public function export_paging() {
		$output = '';
		if (!empty($this->paging)) {
			if ($this->paging_mode == 1) {
				if ($this->output_mode == 1) {
					if ($this->actual_page['page'] > $this->around + 1) {
						$output .= sprintf($this->pattern['link'],
								$this->first_page['href'],
								$this->first_page['title'], 'prev',
								$this->first_page['page']);
					}
					if ($this->actual_page['page'] > $this->around + 2) {
						$output .= $this->pattern['dots'];
					}
					if ($this->actual_page['page'] == $this->around + 2) {
						$output .= $this->pattern['separator'];
					}
					foreach ($this->paging['prev'] as $key => $value) {
						$output .= sprintf($this->pattern['link'],
								$value['href'], $value['title'], 'prev',
								$value['page']) . $this->pattern['separator'];
					}
					$output .= sprintf($this->pattern['active'],
							$this->actual_page['page']);
					if ($this->actual_page['key'] != $this->last_page['key']) {
						$output .= $this->pattern['separator'];
					}
					foreach ($this->paging['next'] as $key => $value) {
						$output .= sprintf($this->pattern['link'],
								$value['href'], $value['title'], 'next',
								$value['page'])
								. ($key < count($this->paging['next']) - 1 ? $this
												->pattern['separator'] : '');
					}
					if ($this->actual_page['page']
							< $this->last_page['key'] - $this->around) {
						$output .= $this->pattern['dots'];
					}
					if ($this->actual_page['page']
							== $this->last_page['key'] - $this->around) {
						$output .= $this->pattern['separator'];
					}
					if ($this->actual_page['page']
							< $this->last_page['key'] - $this->around + 1) {
						$output .= sprintf($this->pattern['link'],
								$this->last_page['href'],
								$this->last_page['title'], 'next',
								$this->last_page['page']);
					}
				} else {
					if ($this->actual_page['page'] > $this->around + 1) {
						$output .= sprintf($this->pattern['link'],
								$this->first_page['href'],
								$this->first_page['title'], 'prev',
								$this->pattern['marks']['laquo']);
					}
					foreach ($this->paging['prev'] as $key => $value) {
						$output .= sprintf($this->pattern['link'],
								$value['href'], $value['title'], 'prev',
								$this->pattern['marks']['lt']);
					}
					$output .= sprintf($this->pattern['active'],
							$this->actual_page['page']);
					foreach ($this->paging['next'] as $key => $value) {
						$output .= sprintf($this->pattern['link'],
								$value['href'], $value['title'], 'next',
								$this->pattern['marks']['gt']);
					}
					if ($this->actual_page['page']
							< $this->last_page['key'] - $this->around + 1) {
						$output .= sprintf($this->pattern['link'],
								$this->last_page['href'],
								$this->last_page['title'], 'next',
								$this->pattern['marks']['raquo']);
					}
				}
			} else {
				foreach ($this->paging as $value) {
					if ($value['key'] == $this->actual_page['key']) {
						if ($this->output_mode == 1) {
							$output .= sprintf($this->pattern['active'],
									$value['from'] . $this->pattern['dash']
											. $value['to']);
						} else {
							$output .= sprintf($this->pattern['active'],
									$value['page']);
						}
					} else {
						if ($value['key'] < $this->actual_page['key']) {
							$rel = 'prev';
						} else {
							$rel = 'next';
						}
						if ($this->output_mode == 1) {
							$output .= sprintf($this->pattern['link'],
									$value['href'], $value['title'], $rel,
									$value['from'] . $this->pattern['dash']
											. $value['to']);
						} else {
							$output .= sprintf($this->pattern['link'],
									$value['href'], $value['title'], $rel,
									$value['page']);
						}
					}
					if ($value['page'] != $this->last_page['page']) {
						$output .= $this->pattern['separator'];
					}
				}
			}
		}
		return $output;
	}

	/**
	 * get_start
	 * vrati start pro LIMIT SQL dotazu
	 * @return int
	 **/
	public function get_start() {
		return (int) $this->start;
	}

	/**
	 * get_limit
	 * vrati limit pro LIMIT SQL dotazu
	 * @return int
	 **/
	public function get_limit() {
		return (int) $this->limit;
	}

	/**
	 * vrati titulek aktualni stranky jsme-li dal nez na prvni strance
	 * @return array
	 **/
	public function get_title() {
		return ($this->actual ? $this->actual_page['title'] : null);
	}

	/**
	 * pro moznost vlastniho vypsani pole, vracime array se vsim o strankovani
	 * @return array
	 **/
	public function get_paging() {
		return $this->paging;
	}

	/**
	 * zakouzlena metoda na vyechovani objektu
	 * @return string
	 **/
	public function __toString() {
		return $this->export_paging();
	}

	/**
	 * na setovani jsou specialni metody, obecne to zakazeme
	 * @return false
	 **/
	public function __set($name, $value) {
		return false;
	}

	/**
	 * nebudebude se ani getovat
	 * @return false
	 **/
	public function __get($name) {
		return false;
	}
}

?>