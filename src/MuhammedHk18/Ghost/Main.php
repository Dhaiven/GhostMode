<?php

namespace MuhammedHk18\Ghost;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\GameMode;
use pocketmine\player\OfflinePlayer;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\PluginException;
use pocketmine\utils\Config;
use MuhammedHk18\Ghost\EventListener;

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
            #   dont-have-permission: §cYou dont have permission run this command.
            #   ghostlist-header-message: §5Players in ghost mode:
            #   ghostlist-message: - {%0}

            "dont-have-permission" => "§cYou dont have permission run this command.",
            "run-in-game" => "§cPlease use this command in game",
            "turn-off-ghost" => "§5Ghost mode turned off",
            "turn-on-ghost" => "§5Ghost mode turned on",
            "ghostlist-header-message" => "§5Players in ghost mode:",
            "ghostlist-message" => "- {%0}" # {0} = Player name
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
     * @param array $args
     * @return array|bool|mixed|string|string[]
     */

    public function getMessage(string $configVar, array $args = []){
        $message = $this->config->get($configVar) ?? throw new PluginException("please make sure you have set settings.yml properly.");

        foreach($args as $index => $prop){
            $message = str_replace("{%$index}", "", $prop);
        }

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
        var_dump($player->getGamemode());
        self::$ghostPlayers[$player->getName()] = [
            "Position" => $player->getPosition(),
            "World" => $player->getWorld(),
            "Contents" => $player->getInventory()->getContents(),
            "Armors" => $player->getArmorInventory()->getContents(),
            "OffHand" => $player->getOffHandInventory()->getContents(),
            "GameMode" => $player->getGamemode(),
        ];

        $player->removeCurrentWindow(); // close inventory window
        $player->getInventory()->clearAll(); // clear all inventory
        $player->getArmorInventory()->clearAll(); // clear armor inventory
        $player->getOffHandInventory()->clearAll(); // off hand

        $player->setGamemode(GameMode::SPECTATOR()); // spectator mode

    }

    /**
     * @param Player $player
     * @return void
     */

    public function turnOffGhost(Player|OfflinePlayer $player): void{
        $player->teleport(self::$ghostPlayers[$player->getName()]["Position"]);

        $player->setGamemode(self::$ghostPlayers[$player->getName()]["GameMode"]);

        $player->getInventory()->setContents(self::$ghostPlayers[$player->getName()]["Contents"]);
        $player->getArmorInventory()->setContents(self::$ghostPlayers[$player->getName()]["Armors"]);
        $player->getArmorInventory()->setContents(self::$ghostPlayers[$player->getName()]["OffHand"]);

        unset(self::$ghostPlayers[$player->getName()]);
    }

    /**
     * @param CommandSender $sender
     * @param Command $command
     * @param string $label
     * @param array $args
     * @return bool
     */

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

        if($command == "ghostlist"){
            if(!$sender->hasPermission("ghost.listcommand")){
                $sender->sendMessage($this->getMessage("dont-have-permission"));
                return false;
            }

            $sender->sendMessage($this->getMessage("ghostlist-header-message"));

            foreach (self::$ghostPlayers as $players => $index) {
                $sender->sendMessage($this->getMessage("ghostlist-message", [0 => $players]));
            }
        }

        return true;
    }
}