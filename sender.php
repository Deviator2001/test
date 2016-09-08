<?php
class DB extends MySQLi {

    private static $instance;
    private $num_mails;

    private function __construct($host, $user, $password, $database){
        parent::__construct($host, $user, $password, $database);
    }

    public static function getInstance(){
        if (!isset(self::$instance))
        {
            self::$instance = new DB('localhost', 'admin', 'admin', 'test');
        }
        return self::$instance;
    }

    public function selectMails($num_mails)
    {
        //выбор записей с истекающим сроком публикации
        $this->num_mails = $num_mails;

        $query = "SELECT
                        users.email, items.id, items.title, items.link, items.publicated_to
                  FROM
                        items INNER JOIN users
                  ON
                        users.id = items.user_id
                  AND
                        items.status = 2
                  AND
                        TO_DAYS(items.publicated_to) - TO_DAYS(NOW()) IN (SELECT alert FROM alerts)
                  AND
                        UNIX_TIMESTAMP() - UNIX_TIMESTAMP(items.alerted) > 86400
                  LIMIT
                        $this->num_mails";

        $this->mails = self::getInstance()->query($query);
        if (!$this->mails) echo 'Не удалось выполнить запрос на получение записей из базы!';
    }
    public function sendAlert()
    {
        while ($alert = $this->mails->fetch_object()) {

            //расчет дней до конца публикации /как вариант отправлять только дату окончания публикации ($alert->publicated_to)
            $datetime1 = new DateTime($alert->publicated_to);
            $datetime2 = new DateTime();
            $rest_days = $datetime2->diff($datetime1)->format("%d");

            //условная отправка письма с уведомлением
            //mail('$alert->email', '$alert->title', '$alert-link', '$alert->publicated_to', $rest_days);

            //отметка об отправке уведомления
            $query = "UPDATE
                            items
                      SET
                            alerted = NOW()
                      WHERE
                            id = $alert->id";

            self::getInstance()->query($query);
        }
    }
    public function connectclose()
    {
        self::getInstance()->close();
    }
}

$db = DB::getInstance();
$db->selectMails(100);//количество писем за один раз
$db->sendAlert();
$db->connectclose();
?>