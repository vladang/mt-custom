<?php

//+------------------------------------------------------------------+
//|                           MetaTrader 5 Web API Extension Example |
//|                   Copyright 2000-2020, MetaQuotes Software Corp. |
//|                                        http://www.metaquotes.net |
//+------------------------------------------------------------------+

namespace Vladang\MtCustom\Lib;

use Vladang\MtCustom\Lib\mt5\MTWebAPI;

class Mt5Helper
{
    public $info = array();
    private $timeOut = 3; //время ожидания ответа
    private $agent = "WebApi"; //произвольное имя веб-агента
    private $debug = false; //логирование запросов
    private $logDir = "../storage/logs/mt5_logs/"; //каталог логов
    private $timeOpenConnect = 1; //время активного соединения
    private $time;
    private $api;
    public $group = "real\GROUP A";
    public $company = null;

    const BASE_USER_PERMISSIONS = 483;
    const BLOCKED_USER_PERMISSIONS = 0;
    const BLOCKED_USER_TRADE_PERMISSIONS = 7;

    function __construct($config = null)
    {
        $this->info = $config;
        $this->api = new MTWebAPI($this->agent, $this->logDir, false);
        $this->api->SetLoggerWriteDebug($this->debug);
        $this->connect();
        $this->time = time();
    }

    /**
     *  Подключение к серверу
     * @throws Exception
     */
    private function connect()
    {
        $res = $this->api->Connect($this->info['server'], $this->info['port'], $this->timeOut, $this->info['login'], $this->info['password']);

        if ($res != MTRetCode::MT_RET_OK) {
            $error = 'MetaTrader Connection error, code: ' . print_r($res, 1);
            $this->api->Disconnect();
            throw new Exception($error);
        }
    }

    /**
     * Изменение сервера мт
     * в соотвествии с компанией
     * @param $company
     * @return void
     * @throws \Exception
     */
    public function changeMT($company)
    {
        if ($this->company != $company) {
            global $sugar_config;
            $this->company = $company;

            $this->api->Disconnect();

            if (!empty($company)) {
                $this->info = $sugar_config["MT5_company"][$company];
            } else $this->info = $sugar_config["MT5"];

            $this->connect();
        }
    }

    /**
     *  Переподключение после 120 сек.
     * @throws Exception
     */
    private function checkTimeConnect()
    {
        $diff = time() - $this->time;
        if ($diff >= $this->timeOpenConnect) {
            $this->connect();
        }
    }

    /**
     *  Получение информации о балансе средств
     * @param $userLogin
     * @return array
     * @throws Exception
     */
    public function getBalance($userLogin)
    {
        $res = null;

	    $this->checkTimeConnect();
	    $res = $this->api->UserAccountGet($userLogin, $account);

        return array(
            "status" => $res == MTRetCode::MT_RET_OK,
            "result" => $account,
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
        $result = array(
            "status" => false,
            "result" => "",
        );
        $sum = str_replace(',', '', $sum);
        $sum = floatval($sum);
        if ($sum > 0) {
	        $this->checkTimeConnect();
	        $res = $this->api->TradeBalance($userLogin, $type, $sum, $comment, $ticket);
	        $result = array(
		        "status"   => $res == MTRetCode::MT_RET_OK,
		        "ret_code" => $res,
		        "error"    => $res != MTRetCode::MT_RET_OK ? MTRetCode::GetError($res) : '',
		        "result"   => $ticket,
	        );
        }

	    return $result;
    }

    /**
     *  Получение информации о балансе средств
     * @param $userLogin
     * @return array
     * @throws Exception
     */
    public function getUser($userLogin)
    {
	    $this->checkTimeConnect();
	    $res = $this->api->UserGet($userLogin, $account);

        return array(
            "status" => $res == MTRetCode::MT_RET_OK,
            "result" => $account,
        );
    }

    /**
     *  Update user
     * @param $user
     * @param $updatedUser
     * @return array
     * @throws Exception
     */
    public function updateUser($user, $updatedUser)
    {
	    $this->checkTimeConnect();
	    $res = $this->api->UserUpdate($user, $updatedUser);

        return array(
            "status" => $res == MTRetCode::MT_RET_OK,
            "result" => $res,
        );
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
        $result = array(
            "status" => false,
            "result" => "",
        );
        $sum = str_replace(',', '', $sum);
        $sum = floatval($sum);
        $sum = -1 * abs($sum);
        if ($sum < 0) {
	        $this->checkTimeConnect();
	        $res = $this->api->TradeBalance($userLogin, $type, $sum, $comment, $ticket);;

            $result = array(
                "status"   => $res == MTRetCode::MT_RET_OK,
                "ret_code" => $res,
                "error"    => $res != MTRetCode::MT_RET_OK ? MTRetCode::GetError($res) : '',
                "result"   => $ticket,
            );
        }

        return $result;
    }

    /**
     *  Добавление пользователя
     * @param $arParam
     * @return array
     */
    public function userAdd($arParam)
    {
	    $user = MTUser::CreateDefault();
	    foreach ($arParam as $key => $value) {
		    $user->$key = $value;
	    }
	    $res = $this->api->UserAdd($user, $answer);

        return array(
            "code"   => $res,
            "status" => $res == MTRetCode::MT_RET_OK,
            "result" => $answer,
        );
    }

    public function deleteUser($login)
    {
        $res = $this->api->UserDelete($login);
        return array(
            "status" => $res == MTRetCode::MT_RET_OK,
            "result" => $res == MTRetCode::MT_RET_OK ? "User deleted" : MTRetCode::GetError($res),
        );
    }

    /** Послучение списка счетов в группе
     * @param $group
     * @return array
     */
    public function UserLogins($group)
    {
	    $res = $this->api->UserLogins($group, $logins);

        return array(
            "status" => $res == MTRetCode::MT_RET_OK,
            "result" => $logins,
        );
    }

    /** Добавление клиента
     * @param $arParam
     * @return array
     */
    public function addClient($arParam)
    {
        $body = json_encode($arParam);

	    $res = $this->api->CustomSend('CLIENT_ADD', $arParam, $body, $answer, $answer_body);
	    $data = $this->getJson($answer_body);

        return array(
            "status" => $res == MTRetCode::MT_RET_OK,
            "result" => $data,
        );
    }

    /**
     * @param $id
     * @return array
     */
    public function getClient($id)
    {
        $params = array(
            'ID' => $id,
        );
        $body = '';

	    $res = $this->api->CustomSend('CLIENT_GET', $params, $body, $answer, $answer_body);
	    $data = $this->getJson($answer_body);

        return array(
            "status" => $res == MTRetCode::MT_RET_OK,
            "result" => $data,
        );
    }

    /** получение счетов клиента
     * @param $id
     * @return array
     */
    public function getClientLogins($id)
    {
        $params = array(
            'CLIENT' => $id,
        );
        $body = '';

	    $res = $this->api->CustomSend('CLIENT_USER_LOGINS', $params, $body, $answer, $answer_body);
	    $data = $this->getJson($answer_body);

        return array(
            "status" => $res == MTRetCode::MT_RET_OK,
            "result" => $data,
        );
    }

    /** получение всех клиентов
     * @param array $arParams
     * @return array
     */
    public function getAllClients($arParams = array())
    {
        $body = '';

	    $res = $this->api->CustomSend('CLIENT_IDS', $arParams, $body, $answer, $answer_body);
	    $data = $this->getJson($answer_body);

        return array(
            "status" => $res == MTRetCode::MT_RET_OK,
            "result" => $data,
        );
    }

    /** Привязка счета к клиенту
     * @param $clientId
     * @param $userId
     * @return array
     */
    public function bindUserAccountToClient($clientId, $userId)
    {
        $params = array(
            'CLIENT' => $clientId,
            'USER'   => $userId,
        );
        $body = '';

	    $res = $this->api->CustomSend('CLIENT_USER_ADD', $params, $body, $answer, $answer_body);
	    $data = $this->getJson($answer_body);

        return array(
            "status" => $res == MTRetCode::MT_RET_OK,
            "result" => $data,
        );
    }

    /** Отвязка клиента от счета
     * @param $clientId
     * @param $userId
     * @return array
     */
    public function unbindUserAccountToClient($clientId, $userId)
    {
        $params = array(
            'CLIENT' => $clientId,
            'USER'   => $userId,
        );
        $body = '';

	    $res = $this->api->CustomSend('CLIENT_USER_DELETE', $params, $body, $answer, $answer_body);
	    $data = $this->getJson($answer_body);

        return array(
            "status" => $res == MTRetCode::MT_RET_OK,
            "result" => $data,
        );
    }

    public function getBalanceArray($arLogins)
    {
        $params = array(
            'LOGIN' => implode(",", $arLogins),
        );
        $body = '';

	    $res = $this->api->CustomSend('USER_ACCOUNT_GET_BATCH', $params, $body, $answer, $answer_body);
	    $data = $this->getJson($answer_body);

        return array(
            "status" => $res == MTRetCode::MT_RET_OK,
            "result" => $data,
        );
    }

    /**
     * @param string $from
     * @param string $to
     * @param array $arLogins
     * @param array|null $symbols
     * @return array
     */
    public function getDeals(string $from, string $to, array $arLogins, ?array $symbols)
    {
        $params = array(
            'LOGIN' => implode(",", $arLogins),
            'FROM'  => $from,
            'TO'    => $to,
        );
        $body = '';


	    $res = $this->api->CustomSend('DEAL_GET_BATCH', $params, $body, $answer, $answer_body);
	    $data = $this->getJson($answer_body);

        if ($data) {
            $data = array_filter($data, function ($element) {
                return (float)$element['Profit'] != 0 && $element['Symbol'] != "";
            });

            if ($symbols) {
                $data = array_filter($data, function ($element) use ($symbols) {
                    return in_array($element['Symbol'], $symbols);
                });
            }
        }

        return array(
            "status" => $res == \MTRetCode::MT_RET_OK,
            "result" => $data,
        );
    }

    /**
     * @param array $arLogins
     * @param array|null $symbols
     * @return array
     */
    public function getPositions(array $arLogins, ?array $symbols)
    {
        $params = array(
            'LOGIN' => implode(",", $arLogins),
        );

        $body = '';

	    $res = $this->api->CustomSend('POSITION_GET_BATCH', $params, $body, $answer, $answer_body);
	    $data = $this->getJson($answer_body);

        if ($data) {
            if ($symbols) {
                $data = array_filter($data, function ($element) use ($symbols) {
                    return in_array($element['Symbol'], $symbols);
                });
            }
        }

        return array(
            "status" => $res == \MTRetCode::MT_RET_OK,
            "result" => $data,
        );
    }

    public function getCloseOrder($from, $to, $arLogins): array
    {
        $params = array(
            'LOGIN' => implode(",", $arLogins),
            'FROM'  => $from,
            'TO'    => $to,
        );
        $body = '';

	    $res = $this->api->CustomSend('HISTORY_GET_BATCH', $params, $body, $answer, $answer_body);
	    $answer_body = str_replace("0<", '', $answer_body);
	    $data = $this->getJson($answer_body);

        return array(
            "status" => $res == \MTRetCode::MT_RET_OK,
            "result" => $data,
        );
    }

    public function getOpenOrder($arLogins)
    {
        $params = array(
            'LOGIN' => implode(",", $arLogins),
        );
        $body = '';

	    $res = $this->api->CustomSend('ORDER_GET_BATCH', $params, $body, $answer, $answer_body);
	    $data = $this->getJson($answer_body);

        return array(
            "status" => $res == \MTRetCode::MT_RET_OK,
            "result" => $data,
        );
    }

    public function getJson($json)
    {
        $res = json_decode($json, false);
        if ($res == null) {
            $res = str_replace(array("'", " . ", '"\0"'), '', var_export($json, true));
            $res = preg_replace("/\s+/", "", $res);
            $res = json_decode($res, true);
        }
        return $res;
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
                    if ($lengthBalance > 0) {
                        $first = substr($balance, 0, $lengthBalance - 2);
                        $last = substr($balance, -2);
                        $balance = $first . "." . $last;
                    }
                    if ($lengthCredit > 0) {
                        $firstCredit = substr($credit, 0, $lengthCredit - 2);
                        $lastCredit = substr($credit, -2);
                        $credit = $firstCredit . "." . $lastCredit;
                    }
                    echo '
                            <tr>
                                <td>' . $arValue["Login"] . '</td>
                                <td>' . $balance . '</td>
                                <td>' . $credit . '</td>
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
            if (empty($res)) {
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

    public function changePassword($login, $newPassword): bool
    {
        return MTRetCode::MT_RET_OK == $this->api->UserPasswordChange($login, $newPassword);
    }


    function __destruct()
    {
        $this->api->Disconnect();
    }
}
