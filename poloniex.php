<?php
	// FINAL TESTED CODE - Created by Compcentral
	
	// NOTE: currency pairs are reverse of what most exchanges use...
	//       For instance, instead of XPM_BTC, use BTC_XPM
	class poloniex {
		protected $api_key;
		protected $api_secret;
		protected $trading_url = "https://poloniex.com/tradingApi";
		protected $public_url = "https://poloniex.com/public";
		
		public function __construct($api_key, $api_secret) {
			$this->api_key = $api_key;
			$this->api_secret = $api_secret;
		}
			
		private function query(array $req = array()) {
			// API settings
			$key = $this->api_key;
			$secret = $this->api_secret;
		 
			// generate a nonce to avoid problems with 32bit systems
			$mt = explode(' ', microtime());
			$req['nonce'] = $mt[1].substr($mt[0], 2, 6);
		 
			// generate the POST data string
			$post_data = http_build_query($req, '', '&');
			$sign = hash_hmac('sha512', $post_data, $secret);
		 
			// generate the extra headers
			$headers = array(
				'Key: '.$key,
				'Sign: '.$sign,
			);

			// curl handle (initialize if required)
			static $ch = null;
			if (is_null($ch)) {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_USERAGENT, 
					'Mozilla/4.0 (compatible; Poloniex PHP bot; '.php_uname('a').'; PHP/'.phpversion().')'
				);
			}
			curl_setopt($ch, CURLOPT_URL, $this->trading_url);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

			// run the query
			$res = curl_exec($ch);

			if ($res === false) throw new Exception('Curl error: '.curl_error($ch));
			//echo $res;
			$dec = json_decode($res, true);
			if (!$dec){
				//throw new Exception('Invalid data: '.$res);
				return false;
			}else{
				return $dec;
			}
		}
		
		protected function retrieveJSON($URL) {
			$opts = array('http' =>
				array(
					'method'  => 'GET',
					'timeout' => 10 
				)
			);
			$context = stream_context_create($opts);
			$feed = file_get_contents($URL, false, $context);
			$json = json_decode($feed, true);
			return $json;
		}

        public function get_deposits_withdrawals($start, $end) {
            return $this->query(
                array(
                'command' => 'returnDepositsWithdrawals',
                'start' => $start,
                'end' => $end
                )
            );
        }

        public function return_chart_data($currency, $start, $end, $period) {
            return $this->query(
                array(
                    'command' => 'returnChartData',
                    'currencyPair' => $currency,
                    'start' => $start,
                    'end' => $end,
                    'period' => $period
                )
            );
        }
        
		public function get_balances() {
			return $this->query( 
				array(
					'command' => 'returnBalances'
				)
			);
		}

        public function get_complete_balances() {
            return $this->query(
                array(
                    'command' => 'returnCompleteBalances'
                )
            );
        }

        public function getBalances () {
            $balances = array ();
            $tbalances = $this->get_complete_balances();
            foreach (array_keys($tbalances) as $currency) {
                $available = $tbalances[$currency]["available"];
                $onorder = $tbalances[$currency]["onOrders"];
                if (($available + $onorder) == 0) continue;
                $balance = array ();
                $balance["name"] = "Poloniex - ".$currency;
                $balance["currency"] = $currency;
                $balance["available"] = $available;
                $balance["onorder"] = $onorder;
                $balances[] = $balance;
            }
            return $balances;
        }
		
		public function get_open_orders($pair) {		
			return $this->query( 
				array(
					'command' => 'returnOpenOrders',
					'currencyPair' => strtoupper($pair)
				)
			);
		}
		
		public function get_my_trade_history($pair) {
			return $this->query(
				array(
					'command' => 'returnTradeHistory',
					'currencyPair' => strtoupper($pair)
				)
			);
		}
		
		public function buy($pair, $rate, $amount) {
			return $this->query( 
				array(
					'command' => 'buy',	
					'currencyPair' => strtoupper($pair),
					'rate' => $rate,
					'amount' => $amount
				)
			);
		}
		
		public function sell($pair, $rate, $amount) {
			return $this->query( 
				array(
					'command' => 'sell',	
					'currencyPair' => strtoupper($pair),
					'rate' => $rate,
					'amount' => $amount
				)
			);
		}
		
		public function cancel_order($pair, $order_number) {
			return $this->query( 
				array(
					'command' => 'cancelOrder',	
					'currencyPair' => strtoupper($pair),
					'orderNumber' => $order_number
				)
			);
		}
		
		public function withdraw($currency, $amount, $address) {
			return $this->query( 
				array(
					'command' => 'withdraw',	
					'currency' => strtoupper($currency),				
					'amount' => $amount,
					'address' => $address
				)
			);
		}
		
		public function get_trade_history($pair) {
			$trades = $this->retrieveJSON($this->public_url.'?command=returnTradeHistory&currencyPair='.strtoupper($pair));
			return $trades;
		}
		
		public function get_order_book($pair) {
			$orders = $this->retrieveJSON($this->public_url.'?command=returnOrderBook&currencyPair='.strtoupper($pair));
			return $orders;
		}
		
		public function get_volume() {
			$volume = $this->retrieveJSON($this->public_url.'?command=return24hVolume');
			return $volume;
		}
	
		public function get_ticker($pair = "ALL") {
			$pair = strtoupper($pair);
			$prices = $this->retrieveJSON($this->public_url.'?command=returnTicker');
			if($pair == "ALL"){
				return $prices;
			}else{
				$pair = strtoupper($pair);
				if(isset($prices[$pair])){
					return $prices[$pair];
				}else{
					return array();
				}
			}
		}
		
		public function get_trading_pairs() {
			$tickers = $this->retrieveJSON($this->public_url.'?command=returnTicker');
			return array_keys($tickers);
		}
		
		public function get_total_btc_balance() {
			$balances = $this->get_balances();
			$prices = $this->get_ticker();
			
			$tot_btc = 0;
			
			foreach($balances as $coin => $amount){
				$pair = "BTC_".strtoupper($coin);
			
				// convert coin balances to btc value
				if($amount > 0){
					if($coin != "BTC"){
						$tot_btc += $amount * $prices[$pair];
					}else{
						$tot_btc += $amount;
					}
				}

				// process open orders as well
				if($coin != "BTC"){
					$open_orders = $this->get_open_orders($pair);
					foreach($open_orders as $order){
						if($order['type'] == 'buy'){
							$tot_btc += $order['total'];
						}elseif($order['type'] == 'sell'){
							$tot_btc += $order['amount'] * $prices[$pair];
						}
					}
				}
			}

			return $tot_btc;
		}

        public static function show_setup() {
            printf ("Please fill out the following information to setup your Poloniex account configuration. ");
            printf ("You will need a full access API key to continue.<br><br>\r\n");
            printf ("<table>\r\n");
            printf ("<tr>\r\n");
            printf ("<td>Name:</td>\r\n");
            printf ("<td><input type=\"text\" name=\"account_name\" value=\"Poloniex\"></td>\r\n");
            printf ("</tr>\r\n");
            printf ("<tr>\r\n");
            printf ("<td>API key:</td>\r\n");
            printf ("<td><input type=\"text\" name=\"api_key\"></td>\r\n");
            printf ("</tr>\r\n");
            printf ("<tr>\r\n"); 
            printf ("<td>API secret:</td>\r\n");
            printf ("<td><input type=\"text\" name=\"api_secret\"></td>\r\n");
            printf ("</tr>\r\n");
            printf ("</table>\r\n");
        }

        public function show_backfill () {
        }

        public static function get_url ($name) {
            if (strstr($name, "Poloniex")) {
                return ("https://poloniex.com/");
            }
            return ("");
        }

    }

?>
