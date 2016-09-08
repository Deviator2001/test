<?php
class DB {

    private static $instance;
    private $host;
    private $user;
    private $password;
    private $dbname;
    private $num_mails;
    private $mails;
    private $connect;

    private function __construct($host, $user, $password, $dbname)
    {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
        $this->dbname = $dbname;
        $this->opt  = array
        (
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_EMULATE_PREPARES   => TRUE,
        );
        $dsn = "mysql:host=$this->host; dbname=$this->dbname; charset=utf8";
        $this->connect = new PDO($dsn, $this->user, $this->password, $this->opt);

    }

    private function __clone() {
    }

    private function __wakeup() {
    }


    public static function getInstance(){
        if (!isset(self::$instance))
        {
            self::$instance = new self('localhost', 'admin', 'admin', 'test');
        }
        return self::$instance;
    }

    /*public function query($sql)
    {
        return @mysqli_query(self::$instance->connect,$sql);
    }

    //возвращает запись в виде объекта
    public function fetch_object($object)
    {
        return @mysqli_fetch_object($object);
    }
*/
    public function selectMails($num_mails)
    {
        //выбор записей с истекающим сроком публикации
        $this->num_mails = $num_mails;

        $this->mails = $this->connect->prepare(
                 'SELECT
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
                        :limit');
        $this->mails->bindParam(':limit', $num_mails);
        $num_mails = $this->num_mails;
        $this->mails->execute();
        if (!$this->mails) echo 'Не удалось выполнить запрос на получение записей из базы!';
    }

    /**
     *
     */
    public function sendAlert()
    {
        while ($alert = $this->mails->fetch()) {

            //расчет дней до конца публикации /как вариант отправлять только дату окончания публикации ($alert->publicated_to)
            $datetime1 = new DateTime($alert->publicated_to);
            $datetime2 = new DateTime();
            $rest_days = $datetime2->diff($datetime1)->format("%d");

            //условная отправка письма с уведомлением
            //mail('$alert->email', '$alert->title', '$alert-link', '$alert->publicated_to', $rest_days);

            //отметка об отправке уведомления

            $this->insert = $this->connect->prepare(
                     'UPDATE
                            items
                      SET
                            alerted = NOW()
                      WHERE
                            id = :id_item');
            $this->insert->bindParam(':id_item', $id_item);
            $id_item = $alert->id;
            $this->insert->execute();
        }
    }
    function connectclose()
    {
        $this->connect->close();
    }
}


$db = DB::getInstance();
$db->selectMails(100);//количество писем за один раз
$db->sendAlert();
//$db->connectclose();//соединение закроется навсегда, так как новое образуется только при создании экземпляра класса
?>