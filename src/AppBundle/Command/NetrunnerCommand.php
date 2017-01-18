<?php

namespace AppBundle\Command;

use Alsciende\ConsoleShellBundle\Annotation\Context;
use Alsciende\ConsoleShellBundle\Annotation\Command;
use Alsciende\ConsoleShellBundle\Annotation\Help;
use Guzzle\Cache\DoctrineCacheAdapter;
use Guzzle\Plugin\Cache\CachePlugin;
use Guzzle\Plugin\Cache\DefaultCacheStorage;

/**
 * Description of FileExplorerCommand
 *
 * @author cbertolini
 * 
 */
class NetrunnerCommand extends \Symfony\Component\Console\Command\Command
{

    use \Alsciende\ConsoleShellBundle\Traits\ShellTrait;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var \GuzzleHttp\Client
     */
    private $client;
    private $cycle;
    private $pack;
    private $card;

    protected function configure ()
    {
        $this
                ->setName('app:netrunner')
                ->setDescription('Explore Android:Netrunner.')
        ;
    }

    protected function execute (\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $this->client = new \GuzzleHttp\Client([
            'base_uri' => 'https://netrunnerdb.com/api/2.0/public/'
        ]);

        $this->shellLaunch($input, $output, "top");
    }

    protected function writeError ($message)
    {
        $formatter = $this->getHelper('formatter');
        $formattedBlock = $formatter->formatBlock($message, 'error', true);
        $this->output->writeln($formattedBlock);
    }

    protected function writeSuccess ($message)
    {
        $this->output->writeln("<info>$message</info>");
    }

    /**
     * @Command("quit")
     * @Help("Quit the application")
     */
    public function quit ()
    {
        $this->output->writeln("<comment>Goodbye</comment>");
        die;
    }

    /**
     * @Command("help")
     * @Help("Print help information about the available commands or a specific command")
     * @param string $command
     */
    private function help ($command = null)
    {
        $this->shellHelp($this->output, $command);
    }

    /**
     * @Context("top")
     * @Command("list")
     * @Help("List cycles")
     * @return boolean
     */
    public function listCycles ()
    {
        $response = $this->client->get('cycles');
        $cycles = json_decode($response->getBody()->getContents(), TRUE);
        foreach($cycles['data'] as $cycle) {
            $this->output->writeln($cycle['name']);
        }
        return TRUE;
    }

    /**
     * @Context("top")
     * @Command("cycle")
     * @Help("Select cycle")
     * @return boolean
     */
    public function selectCycle ($code)
    {
        $response = $this->client->get("cycle/$code");
        if($response->getStatusCode() !== 200) {
            $this->writeError($response->getReasonPhrase());
            return FALSE;
        }

        $contents = \GuzzleHttp\json_decode($response->getBody(), TRUE);
        $this->shellContext('cycle');
        $this->cycle = $contents['data'][0];
        return TRUE;
    }

    /**
     * @Context("cycle")
     * @Command("list")
     * @Help("List packs")
     * @return boolean
     */
    public function listPacks ()
    {
        $response = $this->client->get('packs');
        $packs = json_decode($response->getBody()->getContents(), TRUE);
        foreach($packs['data'] as $pack) {
            if($pack['cycle_code'] === $this->cycle['code']) {
                $this->output->writeln($pack['name']);
            }
        }
        return TRUE;
    }

    /**
     * @Context("cycle")
     * @Command("pack")
     * @Help("Select pack")
     * @return boolean
     */
    public function selectPack ($code)
    {
        $response = $this->client->get("pack/$code");
        if($response->getStatusCode() !== 200) {
            $this->writeError($response->getReasonPhrase());
            return FALSE;
        }

        $contents = \GuzzleHttp\json_decode($response->getBody(), TRUE);
        $this->shellContext('pack');
        $this->pack = $contents['data'][0];
        return TRUE;
    }

    /**
     * @Context("pack")
     * @Command("list")
     * @Help("List cards")
     * @return boolean
     */
    public function listCards ()
    {
        $response = $this->client->get('cards');
        $cards = json_decode($response->getBody()->getContents(), TRUE);
        foreach($cards['data'] as $card) {
            if($card['pack_code'] === $this->pack['code']) {
                $this->output->writeln($card['title']);
            }
        }
        return TRUE;
    }

}
