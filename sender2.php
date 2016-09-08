<?php

class DB
{
    private $host;
    private $user;
    private $password;
    private $dbname;
    private $connect;
    private $mails;
    private $num_mails;

    function __construct($host,$user,$password,$dbname)
    {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
        $this->dbname = $dbname;
    }

    public function connect()
    {
        $this->connect = new mysqli($this->host, $this->user, $this->password, $this->dbname);
        if (!$this->connect) echo 'Не удалось подключиться к базе!';
    }

    public function selectItems($num_items)
    {
        //выбор записей с истекающим сроком публикации
        $this->num_items = $num_items;

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
                        $this->num_items";

        $this->items = $this->connect->query($query);
        if (!$this->items) echo 'Не удалось выполнить запрос на получение записей из базы!';

        //отправка предупреждений о истекающем сроке
        while ($item = $this->items->fetch_object())
        {
            $alert = new Alert($item->email, $item->title, $item->link, $item->publicated_to);

        //отметка об отправке предупреждения
            $query = "UPDATE
                            items
                      SET
                            alerted = NOW()
                      WHERE
                            id = $item->id";

            $this->connect->query($query);
        }

    }

    function connectclose()
    {
        $this->connect->close();
    }

}

class Alert
{
    private $email;
    private $title;
    private $link;
    private $publicated_to;

    function __construct($email, $title, $link, $publicated_to)
    {
        $this->email = $email;
        $this->title = $title;
        $this->link = $link;
        $this->publicated_to = $publicated_to;

        echo "Кому:$email.<br/>Срок действия вашего объявления: $title по ссылке $link истекает:$publicated_to<br/><br/>";

    }

}


$db = new DB('localhost', 'admin', 'admin', 'test');
$db->connect();
$db->selectItems(100);//количество писем за один раз
$db->connectclose();
?>