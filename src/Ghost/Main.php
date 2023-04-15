<?php

namespace Ghost;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\OfflinePlayer;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\PluginException;
use pocketmine\utils\Config;
use pocketmine\player\GameMode;

class Main extends PluginBase{

    /**
     * @var array
     * array type = [PlayerName => coordinates , world]
     */
    public static array $ghostPlayers = [];

    /** @var $api Main */
    private static $api;


    public function onLoad(): void
    {
        self::$api = $this;
    }

    /** @return Main */

    public static function getInstance(): Main{
        return self::$api;
    }

    public function onEnable(): void
    {

        $this->config = new Config($this->getDataFolder()."settings.yml", Config::YAML, [
            # Use the § symbol for color coding
            # Color code list https://minecraft.tools/en/color-code.php
            # Default Config:
            #   run-in-game: §cPlease use this command in game
            #   turn-off-ghost: §5Ghost mode turned off
            #   turn-on-ghost: §5Ghost mode turned on
            "run-in-game" => "§cPlease use this command in game",
            "turn-off-ghost" => "§5Ghost mode turned off",
            "turn-on-ghost" => "§5Ghost mode turned on"
        ]);
        $this->config->save();
        $this->saveDefaultConfig();
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
    }

    /**
     * @return void
     */

    public function onDisable(): void{
        foreach(self::$ghostPlayers as $player => $index){
            $this->turnOffGhost($this->getServer()->getPlayerByPrefix($player));
        }
    }

    /**
     * @param string $configVar
     * @return bool|mixed
     */

    public function getMessage(string $configVar){
        $message = $this->config->get($configVar) ?? throw new PluginException("please make sure you have set settings.yml properly.");
        return $message;
    }

    /**
     * @param $player
     * @return bool
     */

    public function isGhost($player): bool{
        if($player instanceof Player) $player = $player->getName();
        return isset(self::$ghostPlayers[$player]);
    }

    /**
     * @param Player $player
     * @return void
     */

    public function turnOnGhost(Player $player): void{
        self::$ghostPlayers[$player->getName()] = [
            "Position" => $player->getPosition(),
            "World" => $player->getWorld(),
            "Contents" => $player->getInventory()->getContents(),
            "Armors" => $player->getArmorInventory()->getContents()
        ];
        $player->getInventory()->clearAll(); // clear all inventory
        $player->getArmorInventory()->clearAll(); // clear armor inventory

        $player->setGamemode(GameMode::fromString("spectator")); // spectator mode
    }

    /**
     * @param Player $player
     * @return void
     */

    public function turnOffGhost(Player|OfflinePlayer $player): void{
        $player->teleport(self::$ghostPlayers[$player->getName()]["Position"]);
        $player->setGamemode(GameMode::fromString("survival"));
        $player->getInventory()->setContents(self::$ghostPlayers[$player->getName()]["Contents"]);
        $player->getArmorInventory()->setContents(self::$ghostPlayers[$player->getName()]["Armors"]);
        unset(self::$ghostPlayers[$player->getName()]);
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        if($command == "ghost"){

            if(!$sender instanceof Player){
                $sender->sendMessage($this->getMessage("run-in-game"));
                return false;
            }

            if($this->isGhost($sender)){
                $this->turnOffGhost($sender);
                $sender->sendMessage($this->getMessage("turn-off-ghost"));
            }else{
                $this->turnOnGhost($sender);
                $sender->sendMessage($this->getMessage("turn-on-ghost"));
            }
        }

        return true;
    }
}