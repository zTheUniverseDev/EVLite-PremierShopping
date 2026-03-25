<?php

namespace EVShop;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;

class Main extends PluginBase {

    public function onEnable() {
        $this->saveDefaultConfig();
        $config = $this->getConfig()->get('comandos', []);
        $unknownMessage = $this->getConfig()->get('unknown-subcommand', "&cSubcomando '{subcommand}' no existe.");
        $noMoneyMessage = $this->getConfig()->get('no-money-message', "&cNo tienes suficiente dinero.");
        $invalidQuantityMessage = $this->getConfig()->get('invalid-quantity-message', "&cCantidad inválida.");

        if (empty($config)) {
            $this->getLogger()->warning("No hay comandos definidos en config.yml");
            return;
        }

        $commandMap = $this->getServer()->getCommandMap();
        foreach ($config as $commandName => $commandData) {
            if (!is_array($commandData)) {
                $this->getLogger()->warning("El comando '$commandName' no está definido correctamente");
                continue;
            }

            $aliases = isset($commandData['aliases']) ? (array)$commandData['aliases'] : [];
            unset($commandData['aliases']);

            $command = new ShopCommand($commandName, $commandData, $unknownMessage, $noMoneyMessage, $invalidQuantityMessage);
            $command->setAliases($aliases);
            $commandMap->register($this->getName(), $command);
        }
    }
}
