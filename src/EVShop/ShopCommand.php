<?php

namespace EVShop;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\level\sound\ExpPickupSound;
use pocketmine\level\sound\FizzSound;
use pocketmine\level\sound\GhastShootSound;
use onebone\economyapi\EconomyAPI;

class ShopCommand extends Command {

    private $commandConfig;
    private $unknownMessage;
    private $noMoneyMessage;
    private $invalidQuantityMessage;

    public function __construct($name, $config, $unknownMessage, $noMoneyMessage, $invalidQuantityMessage) {
        parent::__construct($name, "Comando de tienda", "/$name", []);
        $this->commandConfig = $config;
        $this->unknownMessage = $unknownMessage;
        $this->noMoneyMessage = $noMoneyMessage;
        $this->invalidQuantityMessage = $invalidQuantityMessage;
    }

    public function execute(CommandSender $sender, $label, array $args) {
        if (!$sender instanceof Player) {
            $sender->sendMessage("Este comando solo puede usarse en el juego.");
            return true;
        }

        $node = $this->commandConfig;
        $currentPath = [];
        $unknownArg = null;
        $i = 0;

        foreach ($args as $index => $arg) {
            if (isset($node[$arg]) && is_array($node[$arg])) {
                $node = $node[$arg];
                $currentPath[] = $arg;
                $i++;
            } else {
                $unknownArg = $arg;
                break;
            }
        }

        if ($unknownArg !== null) {
            if (isset($node['item']) && isset($node['precio'])) {
                if ($i === count($args) - 1) {
                    if (is_numeric($unknownArg) && $unknownArg > 0) {
                        $cantidad = (int)$unknownArg;
                        return $this->processPurchase($sender, $node, $cantidad);
                    } else {
                        $this->sendInvalidQuantity($sender, $unknownArg);
                        return true;
                    }
                } else {
                    $this->sendUnknown($sender, $unknownArg);
                    return true;
                }
            } else {
                $this->sendUnknown($sender, $unknownArg);
                return true;
            }
        }

        if (!isset($node['item']) || !isset($node['precio'])) {
            $this->sendHelp($sender, $node, implode(' ', $currentPath));
            return true;
        }

        $defaultCantidad = isset($node['cantidad']) ? (int)$node['cantidad'] : 1;
        $cantidad = $defaultCantidad;

        $remaining = array_slice($args, $i);
        if (count($remaining) === 1) {
            $qtyArg = $remaining[0];
            if (is_numeric($qtyArg) && $qtyArg > 0) {
                $cantidad = (int)$qtyArg;
            } else {
                $this->sendInvalidQuantity($sender, $qtyArg);
                return true;
            }
        } elseif (count($remaining) > 1) {
            $this->sendHelp($sender, $node, implode(' ', $currentPath));
            return true;
        }

        return $this->processPurchase($sender, $node, $cantidad);
    }

    private function processPurchase(Player $sender, array $node, $cantidad) {
        $precioUnitario = (int)$node['precio'];
        $precioTotal = $precioUnitario * $cantidad;

        $economy = EconomyAPI::getInstance();
        $dineroActual = $economy->myMoney($sender);

        if ($dineroActual < $precioTotal) {
            $this->sendNoMoney($sender, $precioTotal, $dineroActual);
            return true;
        }

        $itemData = explode(':', $node['item']);
        $id = (int)$itemData[0];
        $damage = isset($itemData[1]) ? (int)$itemData[1] : 0;
        $item = Item::get($id, $damage, $cantidad);

        $sender->getInventory()->addItem($item);
        $economy->reduceMoney($sender, $precioTotal);

        if (isset($node['_texto_'])) {
            $message = $node['_texto_'];
        } else {
            $message = "&aHas comprado {cantidad} {item} por {precio}$.";
        }
        $message = $this->replacePlaceholders($message, $sender, $precioTotal, $cantidad, $item, $dineroActual - $precioTotal);
        $message = $this->translateColors($message);
        $sender->sendMessage($message);

        $soundType = isset($node['_sonido_']) ? $node['_sonido_'] : 'positivo';
        $this->playSound($sender, $soundType);

        return true;
    }

    private function sendHelp(CommandSender $sender, $node, $path = '') {
        if (isset($node['_ayuda_'])) {
            $helpMsg = $node['_ayuda_'];
        } else {
            $subcommands = array_filter(array_keys($node), function($key) {
                return $key[0] !== '_';
            });
            if (empty($subcommands)) {
                $helpMsg = "&cNo hay subcomandos disponibles.";
            } else {
                $base = $this->getName() . ($path ? ' ' . $path : '');
                $helpMsg = "&aSubcomandos: &e/" . $base . " " . implode(" &7| &e/" . $base . " ", $subcommands);
            }
        }
        $helpMsg = $this->replacePlaceholders($helpMsg, $sender, 0, 0, null, 0);
        $helpMsg = $this->translateColors($helpMsg);
        $sender->sendMessage($helpMsg);
    }

    private function sendUnknown(CommandSender $sender, $badSubcommand) {
        $msg = str_replace('{subcommand}', $badSubcommand, $this->unknownMessage);
        $msg = $this->replacePlaceholders($msg, $sender, 0, 0, null, 0);
        $msg = $this->translateColors($msg);
        $sender->sendMessage($msg);
        $this->playSound($sender, 'negativo');
    }

    private function sendNoMoney(CommandSender $sender, $precio, $dinero) {
        $msg = str_replace('{precio}', $precio, $this->noMoneyMessage);
        $msg = str_replace('{dinero}', $dinero, $msg);
        $msg = $this->replacePlaceholders($msg, $sender, $precio, 0, null, $dinero);
        $msg = $this->translateColors($msg);
        $sender->sendMessage($msg);
        $this->playSound($sender, 'negativo');
    }

    private function sendInvalidQuantity(CommandSender $sender, $qty) {
        $msg = str_replace('{cantidad}', $qty, $this->invalidQuantityMessage);
        $msg = $this->replacePlaceholders($msg, $sender, 0, 0, null, 0);
        $msg = $this->translateColors($msg);
        $sender->sendMessage($msg);
        $this->playSound($sender, 'negativo');
    }

    private function replacePlaceholders($message, Player $player, $precio, $cantidad, $item = null, $dineroRestante = null) {
        $replace = [
            '{player}' => $player->getName(),
            '{online}' => count($player->getServer()->getOnlinePlayers()),
            '{precio}' => $precio,
            '{dinero}' => EconomyAPI::getInstance()->myMoney($player),
            '{fecha}' => date('Y-m-d H:i:s'),
            '{cantidad}' => $cantidad
        ];
        if ($item !== null) {
            $replace['{item}'] = $item->getName();
        }
        if ($dineroRestante !== null) {
            $replace['{restante}'] = $dineroRestante;
        }
        return str_replace(array_keys($replace), array_values($replace), $message);
    }

    private function playSound(Player $player, $soundType) {
        $position = $player->getPosition();
        $sound = null;

        switch ($soundType) {
            case 'positivo':
                $sound = new ExpPickupSound($position);
                break;
            case 'info':
                $sound = new FizzSound($position);
                break;
            case 'negativo':
                $sound = new GhastShootSound($position);
                break;
            default:
                $sound = new ExpPickupSound($position);
                break;
        }

        if ($sound !== null) {
            $player->getLevel()->addSound($sound, [$player]);
        }
    }

    private function translateColors($message) {
        return str_replace('&', '§', $message);
    }
}
