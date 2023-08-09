<?php

namespace Lkt\Connectors;

class ImapConnector extends AbstractMailConnector
{
    /** @var ImapConnector[] */
    protected static array $connectors = [];

    public static function define(string $name): static
    {
        $r = new static($name);
        static::$connectors[$name] = $r;
        return $r;
    }

    public static function get(string $name): ?static
    {
        if (!isset(static::$connectors[$name])) {
            throw new \Exception("Connector '{$name}' doesn't exists");
        }
        return static::$connectors[$name];
    }

    /**
     * @return ImapConnector[]
     */
    public static function getAllConnectors(): array
    {
        return static::$connectors;
    }

    protected $connection = null;

    protected int $port = 993;

    protected const HOST_GMAIL = '{imap.gmail.com:{_PORT_}/imap/ssl/novalidate-cert}INBOX';

    public function setHostToGmail(): static
    {
        $this->host = str_replace('{_PORT_}', $this->port, static::HOST_GMAIL);
        return $this;
    }

    public function connect(): static
    {
        if ($this->connection !== null) return $this;

        // Perform the connection
        try {
            $this->connection = \imap_open($this->host, $this->user, $this->password, OP_READONLY) or die('Cannot connect to Gmail: ' . imap_last_error());

        } catch (\Exception $e) {
            die ('Connection to IMAP failed');
        }
        return $this;
    }

    public function disconnect(): static
    {
        $this->connection = null;
        return $this;
    }

    public function query($url = '', $withAttachments = true): array
    {
        $this->connect();

        /* Search Emails having the specified keyword in the email subject */
        $emailData = \imap_search($this->connection, $url);

        $items = [];
        foreach ($emailData as $emailIdent) {

            $overview = \imap_fetch_overview($this->connection, $emailIdent, 0);
            $message = \imap_fetchbody($this->connection, $emailIdent, '1.1');
            $messageExcerpt = substr($message, 0, 150);
            $partialMessage = trim(quoted_printable_decode($messageExcerpt));
            $date = date("d F, Y", strtotime($overview[0]->date));
            $attachments = array();

            if ($withAttachments === true) {
                $structure = \imap_fetchstructure($this->connection, $emailIdent);
                if (isset($structure->parts) && count($structure->parts)) {
                    for ($i = 0; $i < count($structure->parts); $i++) {
                        $attachments[$i] = array(
                            'is_attachment' => false,
                            'filename' => '',
                            'name' => '',
                            'attachment' => '');

                        if ($structure->parts[$i]->ifdparameters) {
                            foreach ($structure->parts[$i]->dparameters as $object) {
                                if (strtolower($object->attribute) == 'filename') {
                                    $attachments[$i]['is_attachment'] = true;
                                    $attachments[$i]['filename'] = $object->value;
                                }
                            }
                        }

                        if ($structure->parts[$i]->ifparameters) {
                            foreach ($structure->parts[$i]->parameters as $object) {
                                if (strtolower($object->attribute) == 'name') {
                                    $attachments[$i]['is_attachment'] = true;
                                    $attachments[$i]['name'] = $object->value;
                                }
                            }
                        }

                        if ($attachments[$i]['is_attachment']) {
                            $attachments[$i]['attachment'] = \imap_fetchbody($this->connection, $emailIdent, $i + 1);
                            if ($structure->parts[$i]->encoding == 3) { // 3 = BASE64
                                $attachments[$i]['attachment'] = base64_decode($attachments[$i]['attachment']);
                            } elseif ($structure->parts[$i]->encoding == 4) { // 4 = QUOTED-PRINTABLE
                                $attachments[$i]['attachment'] = quoted_printable_decode($attachments[$i]['attachment']);
                            }
                        }
                    }
                }
            }

            $items[] = [
                'overview' => $overview,
                'message' => $message,
                'messageExcerpt' => $messageExcerpt,
                'partialMessage' => $partialMessage,
                'date' => $date,
                'attachments' => $attachments,
            ];
        }

        return $items;
    }
}