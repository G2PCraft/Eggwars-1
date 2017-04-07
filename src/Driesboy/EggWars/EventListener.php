<?php

namespace Driesboy\EggWars;

use pocketmine\entity\Villager;
use pocketmine\event\entity\{
    EntityDamageByEntityEvent, EntityDamageEvent
};
use pocketmine\event\player\{
    PlayerDeathEvent, PlayerInteractEvent, PlayerMoveEvent, PlayerChatEvent
};
use pocketmine\event\block\{
    SignChangeEvent, BlockBreakEvent, BlockPlaceEvent
};
use pocketmine\event\inventory\{
    InventoryTransactionEvent, InventoryCloseEvent
};
use pocketmine\item\Item;
use pocketmine\event\Listener;
use pocketmine\level\{Position, Level};
use pocketmine\{Player, Server};
use pocketmine\tile\{Sign, Chest};
use pocketmine\block\Block;
use pocketmine\math\{AxisAlignedBB, Vector3};
use pocketmine\utils\Config;
use pocketmine\inventory\{
    ChestInventory, PlayerInventory
};

class EventListener implements Listener{

    public $sd = array();
    public function __construct(){
    }

    public function sohbet(PlayerChatEvent $e){
        $o = $e->getPlayer();
        $m = $e->getMessage();
        $main = EggWars::getInstance();

        if($main->IsInArena($o->getName())){
            $color = "";
            $is = substr($m, 0, 1);
            $Team = $main->PlayerTeamColor($o);
            $arena = $main->IsInArena($o->getName());
            $ac = new Config($main->getDataFolder()."Arenas/$arena.yml", Config::YAML);
            if($ac->get("Status") == "Lobby"){
                $Players = $main->ArenaPlayer($arena);
			         foreach($Players as $Is){
			             $to = $main->getServer()->getPlayer($Is);
			             if($to instanceof Player){
			                 $to->sendMessage("§f".$o->getName()." §8» §7".$m);
			             }
			         }
            }
            if(!empty($main->Teams()[$Team])){
                $color = $main->Teams()[$Team];
            }
			     if($is == "!"){
			         $msil = substr($m, 1);
			         $main->ArenaMessage($arena, "§8[§c!§8] ".$color.$o->getName()." §8» §7$msil");
			     }else{
			         $Players = $main->ArenaPlayer($arena);
			         foreach($Players as $Is){
			             $to = $main->getServer()->getPlayer($Is);
			             if($to instanceof Player){
			                 $toTeam = $main->PlayerTeamColor($to);
			                 if($Team == $toTeam){
			                     $message = "§8[".$color."team§8] ".$color.$o->getName()." §8» §7$m";
			                     $to->sendMessage($message);
			                 }
			             }
			         }
			     }
			     return;
        }
    }

    public function KillerGame(PlayerInteractEvent $e){
        $o = $e->getPlayer();
        $b = $e->getBlock();
        $t = $o->getLevel()->getTile($b);
        $main = EggWars::getInstance();
        if($t instanceof Sign){
            $yazilar = $t->getText();
            if($yazilar[0] == $main->tyazi){
                $arena = str_ireplace("§e", "", $yazilar[2]);
                $Status = $main->ArenaStatus($arena);
                if($Status == "Lobby"){
                    if(!$main->IsInArena($o->getName())){
                        $ac = new Config($main->getDataFolder()."Arenas/$arena.yml", Config::YAML);
                        $Players = count($main->ArenaPlayer($arena));
                        $fullPlayer = $ac->get("Team") * $ac->get("PlayersPerTeam");
                        if($Players >= $fullPlayer){
                            $o->sendPopup("§8» §cThis game is full! §8«");
                            return;
                        }
                        $main->AddArenaPlayer($arena, $o->getName());
                        $o->teleport(new Position($ac->getNested("Lobby.X"), $ac->getNested("Lobby.Y"), $ac->getNested("Lobby.Z"), $main->getServer()->getLevelByName($ac->getNested("Lobby.World"))));
                        $o->sendPopup("§aSuccessfully joined the game!");
                        $main->TeamSellector($arena, $o);
                        $main->ArenaMessage($arena, $main->b."§e".$o->getName()." §bjoined the game.");
                    }else{
                        $o->sendPopup("§cYou're already in a game!");
                    }
                }elseif ($Status == "In-Game"){
                    $o->sendPopup("§8» §dThe game is still going on!");
                }elseif ($Status == "Done"){
                    $o->sendPopup("§8» §eResetting the Arena ...");
                }
                $e->setCancelled();
            }
        }
    }

    public function generatorYükselt(PlayerInteractEvent $e){
        $o = $e->getPlayer();
        $b = $e->getBlock();
        $sign = $o->getLevel()->getTile($b);
        $main = EggWars::getInstance();
        if($sign instanceof Sign){
            $y = $sign->getText();
            if($y[0] == "§fIron" || $y[0] == "§6Gold" || $y[0] == "§bDiamond"){
                $tip = $y[0];
                $level = str_ireplace("§eLevel ", "", $y[1]);
                switch($level){
                    case 0:
                        switch ($tip){
                            case "§6Gold":
                                if($main->ItemId($o, Item::GOLD_INGOT) >= 5){
                                    $o->getInventory()->removeItem(Item::get(Item::GOLD_INGOT,0,5));
                                    $sign->setText($y[0], "§eLevel 1", "§b8 seconds", $y[3]);
                                    $o->sendMessage("§8» §aGold generator Activated!");
                                }else{
                                    $o->sendMessage("§8» §65 Gold needed to upgrade!");
                                }
                            break;
                            case "§bDiamond":
                                if($main->ItemId($o, Item::DIAMOND) >= 5){
                                    $o->getInventory()->removeItem(Item::get(Item::DIAMOND,0,5));
                                    $sign->setText($y[0], "§eLevel 1", "§b10 seconds", $y[3]);
                                    $o->sendMessage("§8» §aDiamond generator Activated!");
                                }else{
                                    $o->sendMessage("§8» §b5 Diamonds needed to upgrade!");
                                }
                            break;
                        }
                    break;
                case 1:
                    switch ($tip){
                        case "§fIron":
                            if($main->ItemId($o, Item::IRON_INGOT) >= 10){
                                $o->getInventory()->removeItem(Item::get(Item::IRON_INGOT,0,10));
                                $sign->setText($y[0], "§eLevel 2", "§b2 seconds", $y[3]);
                                $o->sendMessage("§8» §aUpgraded to level 2!");
                            }else{
                                $o->sendMessage("§8» §f10 Iron needed to upgrade!");
                            }
                        break;
                        case "§6Gold":
                            if($main->ItemId($o, Item::GOLD_INGOT) >= 10){
                                $o->getInventory()->removeItem(Item::get(Item::GOLD_INGOT,0,10));
                                $sign->setText($y[0], "§eLevel 2", "§b6 seconds", $y[3]);
                                $o->sendMessage("§8» §aUpgraded to level 2!");
                            }else{
                                $o->sendMessage("§8» §610 Gold needed to upgrade!");
                            }
                        break;
                        case "§bDiamond":
                            if($main->ItemId($o, Item::DIAMOND) >= 10){
                                $o->getInventory()->removeItem(Item::get(Item::DIAMOND,0,10));
                                $sign->setText($y[0], "§eLevel 2", "§b8 seconds", $y[3]);
                                $o->sendMessage("§8» §aUpgraded to level 2!");
                            }else{
                                $o->sendMessage("§8» §b10 Diamonds needed to upgrade!");
                            }
                        break;
                    }
                break;
                case 2:
                    switch ($tip){
                        case "§fIron":
                            if($main->ItemId($o, Item::GOLD_INGOT) >= 10){
                                $o->getInventory()->removeItem(Item::get(Item::GOLD_INGOT,0,10));
                                $sign->setText($y[0], "§eLevel 3", "§b1 seconds", "§c§lMAXIMUM");
                                $o->sendMessage("§8» §aMaximum Level raised!");
                            }else{
                                $o->sendMessage("§8» §610 Gold needed to upgrade!");
                            }
                        break;
                        case "§6Gold":
                            if($main->ItemId($o, Item::DIAMOND) >= 10){
                                $o->getInventory()->removeItem(Item::get(Item::DIAMOND,0,10));
                                $sign->setText($y[0], "§eLevel 3", "§b4 seconds", "§c§lMAXIMUM");
                                $o->sendMessage("§8» §aMaximum Level raised!");
                            }else{
                                $o->sendMessage("§8» §b10 Diamonds needed to upgrade!");
                            }
                        break;
                        case "§bDiamond":
                            if($main->ItemId($o, Item::DIAMOND) >= 20){
                                $o->getInventory()->removeItem(Item::get(Item::DIAMOND,0,20));
                                $sign->setText($y[0], "§eLevel 3", "§b6 seconds", "§c§lMAXIMUM");
                                $o->sendMessage("§8» §aMaximum Level raised!");
                            }else{
                                $o->sendMessage("§8» §b20 Diamonds needed to upgrade!");
                            }
                        break;
                    }
                break;
                default:
                    $o->sendMessage("§8» §cThis generator is already on the Maximum level!");
                break;
                }
            }
        }
    }

    public function yumurtaKir(PlayerInteractEvent $e){
        $o = $e->getPlayer();
        $b = $e->getBlock();
        $main = EggWars::getInstance();
        if($main->IsInArena($o->getName())){
            if($b->getId() == 122){
                $yun = $b->getLevel()->getBlock(new Vector3($b->x, $b->y - 1, $b->z));
                if($yun->getId() == 35){
                    $color = $yun->getDamage();
                    $Team = array_search($color, $main->TeamSearcher());
                    $oht = $main->PlayerTeamColor($o);
                    if($oht == $Team){
                        $o->sendPopup("§8»§c You can not break your own egg!");
                        $e->setCancelled();
                    }else{
                        $b->getLevel()->setBlock(new Vector3($b->x, $b->y, $b->z), Block::get(0));
                        $main->CreateLightning($b->x, $b->y, $b->z, $o->getLevel());
                        $arena = $main->IsInArena($o->getName());
                        $main->ky[$arena][] = $Team;
                        $o->sendPopup("§8» ".$main->Teams()[$Team]." $Team broke your team's egg!");
                        $main->ArenaMessage($main->IsInArena($o->getName()), "§8» ".$o->getNameTag()." Player ".$main->Teams()[$Team]."$Team ".$main->Teams()[$oht]."Broke the team's egg!");
                    }
                }
            }
        }
    }

    public function SignCreate(SignChangeEvent $e){
        $o = $e->getPlayer();
        $main = EggWars::getInstance();
        if($o->isOp()){
            if($e->getLine(0) == "eggwars"){
                if(!empty($e->getLine(1))){
                    if($main->ArenaControl($e->getLine(1))){
                        if($main->ArenaReady($e->getLine(1))){
                            $arena = $e->getLine(1);
                            $e->setLine(0, $main->tyazi);
                            $e->setLine(1, "§f0/0");
                            $e->setLine(2, "§e$arena");
                            $e->setLine(3, "§l§bYukleniyor");
                            for($i=0; $i<=3; $i++){
                                $o->sendMessage("§8» §a$i".$e->getLine($i));
                            }
                        }else{
                            $e->setLine(0, "§cERROR");
                            $e->setLine(1, "§7".$e->getLine(1));
                            $e->setLine(2, "§7Arena");
                            $e->setLine(3, "§7not exactly!");
                        }
                    }else{
                        $e->setLine(0, "§cERROR");
                        $e->setLine(1, "§7".$e->getLine(1));
                        $e->setLine(2, "§7Arena");
                        $e->setLine(3, "§7Not found");
                    }
                }else{
                    $e->setLine(0, "§cERROR");
                    $e->setLine(1, "§7Arena");
                    $e->setLine(2, "§7Section");
                    $e->setLine(3, "§7null!");
                }
            }elseif ($e->getLine(0) == "generator"){
                if(!empty($e->getLine(1))){
                    switch ($e->getLine(1)){
                        case "Iron":
                            $e->setLine(0, "§fIron");
                            $e->setLine(1, "§eLevel 1");
                            $e->setLine(2, "§b4 seconds");
                            $e->setLine(3, "§a§lUpgrade");
                            break;
                        case "Gold":
                            if($e->getLine(2) != "Broken") {
                                $e->setLine(0, "§6Gold");
                                $e->setLine(1, "§eLevel 1");
                                $e->setLine(2, "§b8 seconds");
                                $e->setLine(3, "§a§lUpgrade");
                            }else{
                                $e->setLine(0, "§6Gold");
                                $e->setLine(1, "§eLevel 0");
                                $e->setLine(2, "§c-------");
                                $e->setLine(3, "§a§lBroken");
                            }
                            break;
                        case "Diamond":
                            if($e->getLine(2) != "broken") {
                                $e->setLine(0, "§bDiamond");
                                $e->setLine(1, "§eLevel 1");
                                $e->setLine(2, "§b10 seconds");
                                $e->setLine(3, "§a§lUpgrade");
                            }else{
                                $e->setLine(0, "§bDiamond");
                                $e->setLine(1, "§eLevel 0");
                                $e->setLine(2, "§c-------");
                                $e->setLine(3, "§a§lBroken");
                            }
                            break;
                    }
                }else{
                    $e->setLine(0, "§cERROR");
                    $e->setLine(1, "§7generator");
                    $e->setLine(2, "§7Type");
                    $e->setLine(3, "§7unspecified!");
                }
            }
        }
    }

    public function dying(PlayerDeathEvent $e){
        $o = $e->getPlayer();
        $main = EggWars::getInstance();
        if($main->IsInArena($o->getName())){
            $e->setDeathMessage("");
            $sondarbe = $o->getLastDamageCause();
            if($sondarbe instanceof EntityDamageByEntityEvent){
                $e->setDrops(array());
                $olduren = $sondarbe->getDamager();
                if($olduren instanceof Player){
                    $main->ArenaMessage($main->IsInArena($o->getName()), "§8» ".$o->getNameTag()." was killed by ".$olduren->getNameTag());
                }
            }else{
                $e->setDrops(array());
                if(!empty($this->sd[$o->getName()])){
                    $olduren = $main->getServer()->getPlayer($this->sd[$o->getName()]);
                    if($olduren instanceof Player){
                        $main->ArenaMessage($main->IsInArena($o->getName()), "§8» ".$o->getNameTag()." was killed by ".$olduren->getNameTag());
                    }
                }else{
                    $main->ArenaMessage($main->IsInArena($o->getName()), "§8» ".$o->getNameTag()." drowned!");
                }
            }
        }
    }

    public function Damage(EntityDamageEvent $e){
        $o = $e->getEntity();
        $main = EggWars::getInstance();
        if($e instanceof EntityDamageByEntityEvent){
            $d = $e->getDamager();
            if($o instanceof Villager && $d instanceof Player){
                if($o->getNameTag() == "§6EggWars §fShop"){
                    $e->setCancelled();
                    $main->m[$d->getName()] = "ok";
                    $main->EmptyShop($d);
                }
            }
            if($o instanceof Player && $d instanceof Player){
                if($main->IsInArena($o->getName())){
                    $arena = $main->IsInArena($o->getName());
                    $ac = new Config($main->getDataFolder()."Arenas/$arena.yml", Config::YAML);
                    $Team = $main->PlayerTeamColor($o);
                    if($ac->get("Status") == "Lobby"){
                        $e->setCancelled();
                    }else{
                        $td = substr($d->getNameTag(), 0, 3);
                        $to = substr($o->getNameTag(), 0, 3);
                        if($td == $to){
                            $e->setCancelled();
                        }else{
                            $this->sd[$o->getName()] = $d->getName();
                        }
                    }
                    if($e->getDamage() >= $e->getEntity()->getHealth()){
                        $e->setCancelled();
                        $o->setHealth(20);
                        if($main->yumurtaKirildimi($arena, $Team)){
                            $main->RemoveArenaPlayer($arena, $o->getName());
                        }else{
                            $o->teleport(new Position($ac->getNested("$Team.X"), $ac->getNested("$Team.Y"), $ac->getNested("$Team.Z"), $main->getServer()->getLevelByName($ac->get("World"))));
                            $main->ArenaMessage($arena, "§8» ".$o->getNameTag()." was killed by ".$d->getNameTag());
                        }
                        $o->getInventory()->clearAll();
                    }
                }else{
                    $e->setCancelled();
                }
            }
        }else{
            if($o instanceof Player){
                if($main->IsInArena($o->getName())){
                    $arena = $main->IsInArena($o->getName());
                    $ac = new Config($main->getDataFolder()."Arenas/$arena.yml", Config::YAML);
                    if($ac->get("Status") == "Lobby"){
                        $e->setCancelled();
                    }
                    $Team = $main->PlayerTeamColor($o);
                    $message = null;
                    if(!empty($this->sd[$o->getName()])){
                        $sd = $main->getServer()->getPlayer($this->sd[$o->getName()]);
                        if($sd instanceof Player){
                            unset($this->sd[$o->getName()]);
                            $message = "§8» ".$o->getNameTag()." was killed by ".$sd->getNameTag();
                        }else{
                            $message = "§8» ".$o->getNameTag()." drowned!";
                        }
                    }else{
                        $message = "§8» ".$o->getNameTag()." drowned!";
                    }
                    if($e->getDamage() >= $e->getEntity()->getHealth()){
                        $e->setCancelled();
                        $o->setHealth(20);
                        if($main->yumurtaKirildimi($arena, $Team)){
                            $main->RemoveArenaPlayer($arena, $o->getName());
                        }else{
                            $o->teleport(new Position($ac->getNested("$Team.X"), $ac->getNested("$Team.Y"), $ac->getNested("$Team.Z"), $main->getServer()->getLevelByName($ac->get("World"))));
                            $main->ArenaMessage($arena, $message);
                        }
                        $o->getInventory()->clearAll();
                    }
                }else{
                    $e->setCancelled();
                    $o->setHealth(20);
                    $o->teleport($o->getServer()->getDefaultLevel()->getSafeSpawn());
                }
            }
        }
    }

    /*public function hareket(PlayerMoveEvent $e){
        $o = $e->getPlayer();
        $main = EggWars::getInstance();
        if($o->getLevel() == $o->getServer()->getDefaultLevel()) {
            $tile = Server::getInstance()->getDefaultLevel()->getTiles();
            foreach ($tile as $sign) {
                if ($sign instanceof Sign) {
                    $yazi = $sign->getText();
                    $b = $sign->getBlock();
                    if ($yazi[0] == $main->tyazi) {
                        foreach ($o->getLevel()->getNearbyEntities(new AxisAlignedBB($b->x - 0.5, $b->y - 1, $b->z - 0.5, $b->x+0.5, $b->y + 1, $b->z+0.5)) as $Player) {
                            if ($Player instanceof Player) {
                                $Player->knockBack($o, 0, -1, -1, 0.2);
                            }
                        }
                    }
                }
            }
        }
    }*/

    public function envKapat(InventoryCloseEvent $e){
        $o = $e->getPlayer();
        $env = $e->getInventory();
        $main = EggWars::getInstance();
        if($env instanceof ChestInventory){
            if(!empty($main->m[$o->getName()])){
                $o->getLevel()->setBlock(new Vector3($o->getFloorX(), $o->getFloorY() - 4, $o->getFloorZ()), Block::get(Block::AIR));
                unset($main->m[$o->getName()]);
            }
        }
    }

    /*public function StoreEvent(InventoryTransactionEvent $e)
    {
        $main = EggWars::getInstance();
        $trans = $e->getTransaction()->getTransactions();
        $envanter = $e->getTransaction()->getInventories();

        $o = null;
        $sandikB = null;
        $transfer = null;

        foreach ($trans as $t) {
            foreach($envanter as $env){
                $Held = $env->getHolder();
                if ($Held instanceof Chest) {
                    $sandikB = $Held->getBlock();
                    $transfer = $t;
                }
                if ($Held instanceof Player) {
                    $o = $Held;
                }
            }
        }

        if ($o != null && $sandikB != null && $transfer != null) {
            $main->getLogger()->info("Sa");
            if($o instanceof Player) {
                $shopc = new Config($main->getDataFolder() . "shop.yml", Config::YAML);
                $shop = $shopc->get("shop");
                $sandik = $o->getLevel()->getTile($sandikB);
                if ($sandik instanceof Chest) {
                    $item = $transfer->getTargetItem();
                    $main->getLogger()->info("§a".$item->getId());
                    $senv = $sandik->getInventory();

                    if(empty($main->m[$o->getName()])) {
                        $mis = 0;
                        $main->getLogger()->info("§c".$o->getName());
                        for ($i = 0; $i < count($shop); $i += 2) {
                            if ($item->getId() == $shop[$i]) {
                                $mis++;
                            }
                        }
                        if($mis == count($shop)){
                            $main->m[$o->getName()] = 1;
                        }
                    }
                    if(empty($main->m[$o->getName()])) {
                        $main->getLogger()->info("§b".$o->getName());
                        $is = $senv->getItem(1)->getId();
                        if ($is == 264 || $is == 265 || $is == 266) {
                            $main->m[$o->getName()] = 1;
                        }
                    }

                    if(!empty($main->m[$o->getName()])){
                        $main->getLogger()->info("Deneme 1 => ".$o->getName());
                        if($item->getId() == Item::WOOL && $item->getDamage() == 14){
                            $e->setCancelled(true);
                            $shopc = new Config($main->getDataFolder() . "shop.yml", Config::YAML);
                            $shop = $shopc->get("shop");
                            $sandik->getInventory()->clearAll();
                            for ($i=0; $i < count($shop); $i+=2){
                                $slot = $i / 2;
                                $sandik->getInventory()->setItem($slot, Item::get($shop[$i], 0, 1));
                            }
                        }
                        $transferSlot = 0;
                        for ($i=0; $i<$senv->getSize(); $i++) {
                            if ($senv->getItem($i)->getId() == $item->getId()) {
                                $transferSlot = $i;
                                break;
                            }
                        }
                        $main->getLogger()->info("Deneme 2 => ".$transferSlot);
                        $is = $senv->getItem(1)->getId();
                        if ($transferSlot % 2 != 0 && ($is == 264 || $is == 265 || $is == 266)) {
                            $e->setCancelled(true);
                        }
                        if ($item->getId() == 264 || $item->getId() == 265 || $item->getId() == 266) {
                            $e->setCancelled(true);
                        }
                        if ($transferSlot % 2 == 0 && ($is == 264 || $is == 265 || $is == 266)) {
                            $ucret = $senv->getItem($transferSlot + 1)->getCount();

                            $paran = $main->ItemId($o, $senv->getItem($transferSlot + 1)->getId());
                            $main->getLogger()->info($paran." => ".$ucret);
                            if ($paran >= $ucret) {
                                $o->getInventory()->removeItem(Item::get($senv->getItem($transferSlot + 1)->getId(), 0, $ucret));
                                $o->getInventory()->addItem(Item::get($senv->getItem($transferSlot)->getId(), $senv->getItem($transferSlot)->getDamage(), $senv->getItem($transferSlot)->getCount()));
                            }
                            $e->setCancelled(true);
                        }
                        if($is != 264 || $is != 265 || $is != 266){
                            $e->setCancelled(true);
                            $shopc = new Config($main->getDataFolder() . "shop.yml", Config::YAML);
                            $shop = $shopc->get("shop");
                            for ($i = 0; $i < count($shop); $i += 2) {
                                if ($item->getId() == $shop[$i]) {
                                    $sandik->getInventory()->clearAll();
                                    $suball = $shop[$i + 1];
                                    $slot = 0;
                                    for ($e = 0; $e < count($suball); $e++) {
                                        $sandik->getInventory()->setItem($slot, Item::get($suball[$e][0], 0, $suball[$e][1]));
                                        $slot++;
                                        $sandik->getInventory()->setItem($slot, Item::get($suball[$e][2], 0, $suball[$e][3]));
                                        $slot++;
                                    }
                                    break;
                                }
                            }
                            $sandik->getInventory()->setItem($sandik->getInventory()->getSize() - 1, Item::get(Item::WOOL, 14, 1));
                        }
                    }
                }
            }
        }
    }*/

    public function StoreEvent(InventoryTransactionEvent $e){
        $envanter = $e->getTransaction()->getInventories();
        $trans = $e->getTransaction()->getTransactions();
        $main = EggWars::getInstance();
        $o = null;
        $sb = null;
        $transfer = null;
            foreach($envanter as $env){
                $Held = $env->getHolder();
                if($Held instanceof Chest){
                    $sb = $Held->getBlock();
                }
                if($Held instanceof Player){
                    $o = $Held;
                }
            }

        foreach($trans as $t){
            if($t->getInventory() instanceof PlayerInventory){
                $transfer = $t;
            }
        }

        if($o != null and $sb != null and $transfer != null){

            $shopc = new Config($main->getDataFolder()."shop.yml", Config::YAML);
            $shop = $shopc->get("shop");
            $sandik = $o->getLevel()->getTile($sb);
            if($sandik instanceof Chest){
                $item = $transfer->getTargetItem();
                $si = $sandik->getInventory();

                if(empty($main->m[$o->getName()])){
                    $itemler = 0;
                    for($i=0; $i<count($shop); $i += 2){
                        $slot = $i / 2;
                        if($item->getId() == $shop[$i]){
                            $itemler++;
                        }
                    }
                    if($itemler == count($shop)){
                        $main->m[$o->getName()] = 1;
                    }
                }else{
                    $e->setCancelled();
                    if($item->getId() == 35 && $item->getDamage() == 14){
                        $e->setCancelled();
                        $shopc->reload();
                        $shop = $shopc->get("shop");
                        $sandik->getInventory()->clearAll();
                        for($i=0; $i<count($shop); $i += 2){
                            $slot = $i / 2;
                            $sandik->getInventory()->setItem($slot, Item::get($shop[$i], 0, 1));
                        }
                    }
                    $transSlot = 0;
                    for($i=0; $i<$si->getSize(); $i++){
                        if($si->getItem($i)->getId() == $item->getId()){
                            $transSlot = $i;
                            break;
                        }
                    }
                    $is = $si->getItem(1)->getId();
                    if($transSlot % 2 != 0 && ($is == 264 or $is == 265 or $is == 266)){
                        $e->setCancelled();
                    }
                    if($item->getId() == 264 or $item->getId() == 265 or $item->getId() == 266){
                        $e->setCancelled();
                    }
                    if($transSlot % 2 == 0 && ($is == 264 or $is == 265 or $is == 266)){
                        $ucret = $si->getItem($transSlot + 1)->getCount();
                        $para = $main->ItemId($o, $si->getItem($transSlot + 1)->getId());
                        if($para >= $ucret){
                            $o->getInventory()->removeItem(Item::get($si->getItem($transSlot + 1)->getId(), 0, $ucret));
                            $aitemd = $si->getItem($transSlot);
                            $aitem = Item::get($aitemd->getId(), $aitemd->getDamage(), $aitemd->getCount());
                            $o->getInventory()->addItem($aitem);
                        }
                        $e->setCancelled();
                    }
                    if($is != 264 or $is != 265 or $is != 266){
                        $e->setCancelled();
                        $shopc->reload();
                        $shop = $shopc->get("shop");
                        for($i=0; $i<count($shop); $i+=2){
                            if($item->getId() == $shop[$i]){
                                $sandik->getInventory()->clearAll();
                                $gyer = $shop[$i+1];
                                $slot = 0;
                                for($e=0; $e<count($gyer); $e++){
                                    $sandik->getInventory()->setItem($slot, Item::get($gyer[$e][0], 0, $gyer[$e][1]));
                                    $slot++;
                                    $sandik->getInventory()->setItem($slot, Item::get($gyer[$e][2], 0, $gyer[$e][3]));
                                    $slot++;
                                }
                                break;
                            }
                        }
                        $sandik->getInventory()->setItem($sandik->getInventory()->getSize() - 1, Item::get(Item::WOOL, 14, 1));
                    }
                }
            }
        }

    }

    public function BlockBreakEvent(BlockBreakEvent $e){
        $o = $e->getPlayer();
        $b = $e->getBlock();
        $main = EggWars::getInstance();
        if($main->IsInArena($o->getName())){
            $cfg = new Config($main->getDataFolder()."config.yml", Config::YAML);
            $ad = $main->ArenaStatus($main->IsInArena($o->getName()));
            if($ad == "Lobby"){
                $e->setCancelled(true);
                return;
            }
            $bloklar = $cfg->get("BuildBlocks");
            foreach($bloklar as $blok){
                if($b->getId() != $blok){
                    $e->setCancelled();
                }else{
                    $e->setCancelled(false);
                    break;
                }
            }
        }else{
            if(!$o->isOp()){
                $e->setCancelled(true);
            }
        }
    }

    public function BlockPlaceEvent(BlockPlaceEvent $e){
        $o = $e->getPlayer();
        $b = $e->getBlock();
        $main = EggWars::getInstance();
        $cfg = new Config($main->getDataFolder()."config.yml", Config::YAML);
        if($main->IsInArena($o->getName())){
            $ad = $main->ArenaStatus($main->IsInArena($o->getName()));
            if($ad == "Lobby"){
                if($b->getId() == 35){
                    $arena = $main->IsInArena($o->getName());
                    $tyun = array_search($b->getDamage() ,$main->TeamSearcher());
                    $marena = $main->AvailableTeams($arena);
                    if(in_array($tyun, $marena)){
                        $color = $main->Teams()[$tyun];
                        $o->setNameTag($color.$o->getName());
                        $o->sendPopup("§8» Team $color"."$tyun Selected!");
                    }else{
                        $o->sendPopup("§8» §cTeams must be equal!");
                    }
                    $e->setCancelled();
                }
                return;
            }

                $bloklar = $cfg->get("BuildBlocks");
                foreach($bloklar as $blok){
                    if($b->getId() != $blok){
                        $e->setCancelled();
                    }else{
                        $e->setCancelled(false);
                        break;
                    }
                }
        }else{
            if(!$o->isOp()){
                $e->setCancelled(true);
            }
        }
    }

}
