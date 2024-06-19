<?php

//+------------------------------------------------------------------+
//|                           MetaTrader 5 Web API Extension Example |
//|                   Copyright 2000-2020, MetaQuotes Software Corp. |
//|                                        http://www.metaquotes.net |
//+------------------------------------------------------------------+

namespace Vladang\MtCustom\Lib;

class Mt5New
{
    public $info = array();
    private $timeOut = 5; //время ожидания ответа
    private $debug = true; //логирование запросов
    private $timeOpenConnect = 115; //время активного соединения
    private $time;
    private $manager;
    public $group = "real\GROUP A";

    public $url = "http://000.000.000.000/";
    public  $company = null;

    function __construct($company = null)
    {
        global $sugar_config;

        $this->company = $company;

        if (isset($company) && !empty($sugar_config['MT5_company'][$company]['is_new_api'])) {
            $config        = $sugar_config['MT5_company'][$company];
            $this->info    = $config;
            $this->manager = $config['manager'];
            $this->group   = $config['user_group'];
            $this->url     = 'http://' . $config['server'] . '/' . $config['port'] . '/';
        } else {
            $this->info    = $sugar_config["MT5new"];
            $this->manager = $sugar_config["MT5new"]['manager'];
        }

        $this->connect();
        $this->time = time();
    }

    /**
     *  Подключение к серверу
     * @throws Exception
     */
    private function connect()
    {
//        $post = [
//            'ManagerId' => $this->info['login'],
//            'Password' => $this->info['password'],
//            'Remark' => '',
//            'Server' => $this->info['server']
//        ];
//        $result = $this->sendRequest('Add_Manager', null, $post);
//
//        $this->manager = $result['Result'] ? $result['oValue']:  null;
    }

    /**
     *  Подключение к серверу
     * @param $path
     * @param null $get
     * @param null $post
     * @return false|string
     */
    private function sendRequest($path, $get = null, $post = null)
    {
        if ($get) {
            $ch = curl_init($this->url . $path . http_build_query($get));
        } else {
            $ch = curl_init($this->url. $path);
        }
        if ($post) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post, JSON_UNESCAPED_UNICODE));
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $res = curl_exec($ch);
        curl_close($ch);

        $res = json_decode($res, JSON_UNESCAPED_UNICODE);
        return $res;
    }


    /**
     *  Получение информации о балансе средств
     * @param $userLogin
     * @return array
     * @throws Exception
     */
    public function getBalance($userLogin)
    {
        $post = [
            "ManagerIndex" => $this->manager,
            "lstAccount" => [$userLogin],
        ];
        $res = $this->sendRequest('GetAccountRequest', null, $post);

        return array(
            "status" => true,
            "result" => (object)$res[0]
        );
    }

    /**
     * @param $userLogin
     * @param $sum
     * @param int $type
     * @param string $comment
     * @return array
     * @throws Exception
     */
    public function tradeBalancePlus($userLogin, $sum, $type = 2, $comment = "")
    {
        $post = [
            "Account" => $userLogin,
            "Amount" => $sum,
            "ManagerIndex" => $this->manager,
            "Remark" => $comment
        ];

        if ($type == 2) {
            $path = 'DepositBalance';
        } elseif ($type == 3) {
            $path = 'DepositBalanceCredit';
        } elseif ($type == 6) {
            $path = 'DepositBalanceCredit';
        } else {
            $path = 'DepositBalance';
        }

        $res = $this->sendRequest($path, null, $post);

        return array(
            "status" => $res['Result'],
            "result" => (object)$res
        );
    }

    /**
     *  Получение информации о балансе средств
     * @param $userLogin
     * @return array
     * @throws Exception
     */
    public function getUser($userLogin)
    {
        $post = [
            "Account" => $userLogin,
            "ManagerIndex" => $this->manager,
        ];

//        $res = $this->sendRequest('GetAccountRequest', null, $post);
        $res = json_decode('{
	"Address":"String content",
	"AgentAccount":2147483647,
	"Balance":1.26743233E+15,
	"City":"String content",
	"Comment":"String content",
	"Country":"String content",
	"Credit":1.26743233E+15,
	"Email":"String content",
	"Enable":2147483647,
	"EnableReadOnly":2147483647,
	"Equity":1.26743233E+15,
	"Group":"String content",
	"Id":"String content",
	"Leverage":2147483647,
	"Login":"String content",
	"ManagerIndex":"String content",
	"Margin":1.26743233E+15,
	"MarginFree":1.26743233E+15,
	"MarginLevel":1.26743233E+15,
	"Name":"String content",
	"Password":"String content",
	"PasswordInvestor":"String content",
	"Phone":"String content",
	"PrevBalance":1.26743233E+15,
	"PrevEquity":1.26743233E+15,
	"PrevMonthBalance":1.26743233E+15,
	"PrevMonthEquity":1.26743233E+15,
	"Regdate":4294967295,
	"State":"String content",
	"Status":"String content",
	"Timestamp":4294967295,
	"ZipCode":"String content"
}', JSON_UNESCAPED_UNICODE);

        return array(
            "status" => true,
            "result" => (object)$res
        );
    }

    /**
     *  Получение информации о юзерах
     * @param $userLogins
     */
    public function getUserAll($userLogins = [])
    {
        $post = [
            "ManagerIndex" => $this->manager,
            "lstAccount" => $userLogins,
        ];

        return $this->sendRequest('Get_UserAll', null, $post);
    }

    /**
     * @param $userLogin
     * @param $sum
     * @param string $comment
     * @param int $type
     * @return array
     * @throws Exception
     */
    public function tradeBalanceMinus($userLogin, $sum, $type = 2, $comment = "")
    {
        $post = [
            "Account" => $userLogin,
            "Amount" => $sum,
            "ManagerIndex" => $this->manager,
            "Remark" => $comment
        ];

        if ($type == 2) {
            $path = 'WithdrawBalance';
        } elseif ($type == 3) {
            $path = 'WithdrawBalanceCredit';
        } elseif ($type == 6) {
            $path = 'WithdrawBalanceCredit';
        } else {
            $path = 'WithdrawBalance';
        }

        $res = $this->sendRequest($path, null, $post);

        return array(
            "status" => $res['Result'],
            "result" => (object)$res
        );
    }

    /**
     *  Добавление пользователя
     * @param $arParam
     * @return array
     */
    public function userAdd($arParam)
    {
        $post = [
            "Comment" => $arParam['Comment'],
	        "Email" => $arParam['Email'],
            "Enable" => 1,
            "EnableReadOnly" => 0,
	        "Group" => $this->info['user_group'],
	        "Id" => $arParam['ID'],
	        "Leverage" => 100,
	        "ManagerIndex" => $this->manager,
	        "Name" => $arParam['Name'],
	        "Password" => $arParam['MainPassword'],
	        "PasswordInvestor" => $arParam['InvestPassword'],
	        "Phone" => $arParam['Phone'],
	        "Regdate" => time()
        ];

        $res = $this->sendRequest('CreateAccount', null, $post);

        return array(
            "status" => !empty($res['Login']),
            "result" => (object)$res
        );
    }

    public function showTableBalance($clientId)
    {
        $listAccounts = $this->getClientLogins($clientId);
        if (!empty($listAccounts["result"][$clientId])) {
            $arAccounts = $listAccounts["result"][$clientId];
            $result = $this->getBalanceArray($arAccounts);
            echo '
                <style>
                    .balance table, .balance th, .balance td {
                        border: 1px solid black;
                    }
                </style>
                <table class="balance">
                    <tr>
                        <th width="10%">Account</th>
                        <th width="10%">Balance</th>
                        <th width="10%">Credit</th>
                    </tr>';
            if (!empty($result['result'])) {
                foreach ($result['result'] as $arValue) {
                    $balance = $arValue["Balance"];
                    $credit = $arValue["Credit"];
                    $lengthBalance = strlen($balance);
                    $lengthCredit = strlen($credit);
                    if($lengthBalance > 0) {
                        $first = substr($balance, 0, $lengthBalance-2);
                        $last = substr($balance, -2);
                        $balance = $first.".".$last;
                    }
                    if($lengthCredit > 0) {
                        $firstCredit = substr($credit, 0, $lengthCredit-2);
                        $lastCredit = substr($credit, -2);
                        $credit = $firstCredit.".".$lastCredit;
                    }
                    echo '
                            <tr>
                                <td>'.$arValue["Login"].'</td>
                                <td>'.$balance.'</td>
                                <td>'.$credit.'</td>
                            </tr>
                            ';
                }
            }
            echo '</table>';
        }
    }

    public function showTableBalanceOne($account)
    {
        if (!empty($account)) {
            $res = apcu_fetch($account);
            if(empty($res)) {
                $res = $this->getBalance($account);
                apcu_add($account, $res, 60);
            }
            if (!empty($res["result"]->Login)) {
                echo '
                <style>
                    .balance table, .balance th, .balance td {
                        border: 1px solid black;
                    }
                </style>
                <table class="balance">
                    <tr>
                        <th width="10%">Account</th>
                        <th width="10%">Balance</th>
                        <th width="10%">Credit</th>
                    </tr>';
                $arValue = $res["result"];

                $balance = $arValue->Balance;
                $credit = $arValue->Credit;
                echo '
                            <tr>
                                <td>' . $arValue->Login . '</td>
                                <td>' . $balance . '</td>
                                <td>' . $credit . '</td>
                            </tr>
                            ';
                echo '</table>';
            }
        }
    }

}