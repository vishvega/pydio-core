<?php
/*
 * Copyright 2007-2013 Charles du Jeu - Abstrium SAS <team (at) pyd.io>
 * This file is part of Pydio.
 *
 * Pydio is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Pydio is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Pydio.  If not, see <http://www.gnu.org/licenses/>.
 *
 * The latest code can be found at <http://pyd.io/>.
 *
 */
namespace Pydio\Access\Driver\DataProvider\Provisioning;

require_once(dirname(__FILE__)."/../vendor/autoload.php");

use DOMXPath;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Pydio\Access\Core\AbstractAccessDriver;
use Pydio\Core\Http\Response\SerializableResponseStream;
use Pydio\Core\Model\ContextInterface;
use Pydio\Core\Controller\Controller;
use Pydio\Core\Services\UsersService;
use Zend\Diactoros\Response\JsonResponse;

defined('AJXP_EXEC') or die( 'Access not allowed');

/**
 * Plugin to access the configurations data
 * Class ConfAccessDriver
 * @package Pydio\Access\Driver\DataProvider
 */
class ConfAccessDriver extends AbstractAccessDriver
{

    protected $rootNodes = array(
        "data" => array(
            "LABEL" => "ajxp_conf.110",
            "ICON" => "user.png",
            "DESCRIPTION" => "ajxp_conf.137",
            "CHILDREN" => array(
                "repositories" => array(
                    "AJXP_MIME" => "workspaces_zone",
                    "LABEL" => "ajxp_conf.3",
                    "DESCRIPTION" => "ajxp_conf.138",
                    "ICON" => "hdd_external_unmount.png",
                    "MANAGER" => "Pydio\\Access\\Driver\\DataProvider\\Provisioning\\RepositoriesManager"
                ),
                "users" => array(
                    "AJXP_MIME" => "users_zone",
                    "LABEL" => "ajxp_conf.2",
                    "DESCRIPTION" => "ajxp_conf.139",
                    "ICON" => "users-folder.png",
                    "MANAGER" => "Pydio\\Access\\Driver\\DataProvider\\Provisioning\\UsersManager"
                ),
                "roles" => array(
                    "AJXP_MIME" => "roles_zone",
                    "LABEL" => "ajxp_conf.69",
                    "DESCRIPTION" => "ajxp_conf.140",
                    "ICON" => "user-acl.png",
                    "MANAGER" => "Pydio\\Access\\Driver\\DataProvider\\Provisioning\\RolesManager"
                ),
            )
        ),
        "config" => array(
            "AJXP_MIME" => "plugins_zone",
            "LABEL" => "ajxp_conf.109",
            "ICON" => "preferences_desktop.png",
            "DESCRIPTION" => "ajxp_conf.136",
            "CHILDREN" => array(
                "core"	   	   => array(
                    "AJXP_MIME" => "plugins_zone",
                    "LABEL" => "ajxp_conf.98",
                    "DESCRIPTION" => "ajxp_conf.133",
                    "ICON" => "preferences_desktop.png",
                    "MANAGER" => "Pydio\\Access\\Driver\\DataProvider\\Provisioning\\PluginsManager"
                ),
                "plugins"	   => array(
                    "AJXP_MIME" => "plugins_zone",
                    "LABEL" => "ajxp_conf.99",
                    "DESCRIPTION" => "ajxp_conf.134",
                    "ICON" => "folder_development.png",
                    "MANAGER" => "Pydio\\Access\\Driver\\DataProvider\\Provisioning\\PluginsManager"
                ),
                "core_plugins" => array(
                    "AJXP_MIME" => "plugins_zone",
                    "LABEL" => "ajxp_conf.123",
                    "DESCRIPTION" => "ajxp_conf.135",
                    "ICON" => "folder_development.png",
                    "MANAGER" => "Pydio\\Access\\Driver\\DataProvider\\Provisioning\\PluginsManager"
                ),
            )
        ),
        "admin" => array(
            "LABEL" => "ajxp_conf.111",
            "ICON" => "toggle_log.png",
            "DESCRIPTION" => "ajxp_conf.141",
            "CHILDREN" => array(
                "logs" => array(
                    "LABEL" => "ajxp_conf.4",
                    "DESCRIPTION" => "ajxp_conf.142",
                    "ICON" => "toggle_log.png",
                    "MANAGER" => "Pydio\\Access\\Driver\\DataProvider\\Provisioning\\LogsManager"
                ),
                "diagnostic" => array(
                    "LABEL" => "ajxp_conf.5",
                    "DESCRIPTION" => "ajxp_conf.143",
                    "ICON" => "susehelpcenter.png",
                    "MANAGER" => "Pydio\\Access\\Driver\\DataProvider\\Provisioning\\DiagnosticManager"
                )
            )
        ),
        "developer" => array(
            "LABEL" => "ajxp_conf.144",
            "ICON" => "applications_engineering.png",
            "DESCRIPTION" => "ajxp_conf.145",
            "CHILDREN" => array(
                "actions" => array(
                    "LABEL" => "ajxp_conf.146",
                    "DESCRIPTION" => "ajxp_conf.147",
                    "ICON" => "book.png",
                    "MANAGER" => "Pydio\\Access\\Driver\\DataProvider\\Provisioning\\ActionsManager"
                ),
                "hooks" => array(
                    "LABEL" => "ajxp_conf.148",
                    "DESCRIPTION" => "ajxp_conf.149",
                    "ICON" => "book.png",
                    "MANAGER" => "Pydio\\Access\\Driver\\DataProvider\\Provisioning\\DocumentationManager"
                )
            )
        )
    );

    /**
     * Called internally to populate left menu
     * @param ContextInterface $ctx
     * @return array
     * @throws \Exception
     */
    protected function getMainTree(ContextInterface $ctx){
        $rootNodes = $this->rootNodes;
        $user = $ctx->getUser();
        if ($user != null && $user->getGroupPath() != "/") {
            // Group Admin
            unset($rootNodes["config"]);
            unset($rootNodes["admin"]);
            unset($rootNodes["developer"]);
        }
        Controller::applyHook("ajxp_conf.list_config_nodes", array($ctx, &$rootNodes));
        return $rootNodes;
    }

    /**
     * @param ServerRequestInterface $requestInterface
     * @param ResponseInterface $responseInterface
     * @return bool
     */
    public function preprocessLsApi2(ServerRequestInterface &$requestInterface, ResponseInterface &$responseInterface){

        $uri = $requestInterface->getAttribute("api_uri");
        $vars = $requestInterface->getParsedBody();
        if($uri === "/admin/roles") {
            
            $vars["dir"] = "/data/roles";
            $requestInterface = $requestInterface->withParsedBody($vars);

        }else if(strpos($uri, "/admin/people") === 0){

            $crtPath = "";
            if(isSet($vars["path"]) && !empty($vars["path"]) && $vars["path"] !== "/"){
                $crtPath = $vars["path"];
            }
            $vars["dir"] = "/data/users".$crtPath;
            $requestInterface = $requestInterface->withParsedBody($vars);

        }
        
        
    }
    
    /**
     * @param ServerRequestInterface $requestInterface
     * @param ResponseInterface $responseInterface
     */
    public function listAction(ServerRequestInterface $requestInterface, ResponseInterface &$responseInterface){

        if($requestInterface->getAttribute("action") === "stat"){
            $responseInterface = new JsonResponse(["mode" => true]);
            return;
        }

        if($requestInterface->getAttribute("api") === "v2"){
            
            $uri = $requestInterface->getAttribute("api_uri");
            $vars = $requestInterface->getParsedBody();
            if($uri === "/admin/roles") {

                $vars["dir"] = "/data/roles";
                $requestInterface = $requestInterface->withParsedBody($vars);
                
            }else if($uri === "/admin/workspaces") {

                $vars["dir"] = "/data/repositories";
                $requestInterface = $requestInterface->withParsedBody($vars);

            }else if(strpos($uri, "/admin/people") === 0){
                
                $crtPath = "";
                if(isSet($vars["path"]) && !empty($vars["path"]) && $vars["path"] !== "/"){
                    $crtPath = $vars["path"];
                }
                $vars["dir"] = "/data/users/".ltrim($crtPath, "/");
                $requestInterface = $requestInterface->withParsedBody($vars);
                
            }
        }

        /** @var ContextInterface $ctx */
        $ctx = $requestInterface->getAttribute("ctx");
        $treeManager = new TreeManager($ctx, $this->getName(), $this->getMainTree($ctx));
        $nodesList = $treeManager->dispatchList($requestInterface);
        $responseInterface = $responseInterface->withBody(new SerializableResponseStream($nodesList));

    }

    /**
     * @param ServerRequestInterface $requestInterface
     * @param ResponseInterface $responseInterface
     */
    public function pluginsAction(ServerRequestInterface $requestInterface, ResponseInterface &$responseInterface){
        $pluginManager = new PluginsManager($requestInterface->getAttribute("ctx"), $this->getName());
        $responseInterface = $pluginManager->pluginsActions($requestInterface, $responseInterface);
    }

    /**
     * @param ServerRequestInterface $requestInterface
     * @param ResponseInterface $responseInterface
     */
    public function rolesAction(ServerRequestInterface $requestInterface, ResponseInterface &$responseInterface){
        $pluginManager = new RolesManager($requestInterface->getAttribute("ctx"), $this->getName());
        $responseInterface = $pluginManager->rolesActions($requestInterface, $responseInterface);
    }

    /**
     * @param ServerRequestInterface $requestInterface
     * @param ResponseInterface $responseInterface
     */
    public function usersAction(ServerRequestInterface $requestInterface, ResponseInterface &$responseInterface){
        $pluginManager = new UsersManager($requestInterface->getAttribute("ctx"), $this->getName());
        $action = $requestInterface->getAttribute("action");
        if(strpos($action, "people-") === 0){
            $responseInterface = $pluginManager->peopleApiActions($requestInterface, $responseInterface);
        }else{
            $responseInterface = $pluginManager->usersActions($requestInterface, $responseInterface);
        }
    }

    /**
     * Search users
     * @param ServerRequestInterface $requestInterface
     * @param ResponseInterface $responseInterface
     */
    public function searchUsersAction(ServerRequestInterface $requestInterface, ResponseInterface &$responseInterface) {
        $pluginManager = new UsersManager($requestInterface->getAttribute("ctx"), $this->getName());
        $responseInterface = $pluginManager->search($requestInterface, $responseInterface);
    }

    /**
     * @param ServerRequestInterface $requestInterface
     * @param ResponseInterface $responseInterface
     */
    public function repositoriesAction(ServerRequestInterface $requestInterface, ResponseInterface &$responseInterface){
        $pluginManager = new RepositoriesManager($requestInterface->getAttribute("ctx"), $this->getName());
        $responseInterface = $pluginManager->repositoriesActions($requestInterface, $responseInterface);
    }

    /**
     * @param ServerRequestInterface $requestInterface
     * @param ResponseInterface $responseInterface
     */
    public function editAction(ServerRequestInterface $requestInterface, ResponseInterface &$responseInterface){

        $subAction = $requestInterface->getParsedBody()["sub_action"];
        $requestInterface = $requestInterface->withAttribute("action", $subAction);

        switch($subAction){
            case "edit_plugin_options":
                $this->pluginsAction($requestInterface, $responseInterface);
                break;
            case "edit_role":
            case "post_json_role":
                $this->rolesAction($requestInterface, $responseInterface);
                break;
            case "user_set_lock":
            case "change_admin_right":
            case "user_add_role":
            case "create_user":
            case "user_delete_role":
            case "user_reorder_roles":
            case "users_bulk_update_roles":
            case "save_custom_user_params":
            case "save_repository_user_params":
            case "update_user_pwd":
                $this->usersAction($requestInterface, $responseInterface);
                break;
            case "edit_repository":
            case "create_repository":
            case "edit_repository_label":
            case "edit_repository_data":
            case "get_drivers_definition":
            case "get_templates_definition":
                $this->repositoriesAction($requestInterface, $responseInterface);
                break;
            default:
                break;
        }
    }

    /**
     * @param ServerRequestInterface $requestInterface
     * @param ResponseInterface $responseInterface
     */
    public function deleteAction(ServerRequestInterface $requestInterface, ResponseInterface &$responseInterface){

        $httpVars = $requestInterface->getParsedBody();
        // REST API V1 mapping
        if (isSet($httpVars["data_type"])) {
            switch ($httpVars["data_type"]) {
                case "repository":
                    $httpVars["repository_id"] = basename($httpVars["data_id"]);
                    break;
                case "role":
                    $httpVars["role_id"] = basename($httpVars["data_id"]);
                    break;
                case "user":
                    $httpVars["user_id"] = basename($httpVars["data_id"]);
                    break;
                case "group":
                    $httpVars["group"] = "/data/users".$httpVars["data_id"];
                    break;
                default:
                    break;
            }
            unset($httpVars["data_type"]);
            unset($httpVars["data_id"]);
            $requestInterface = $requestInterface->withParsedBody($httpVars);

        }

        /** @var ContextInterface $ctx */
        $ctx = $requestInterface->getAttribute("ctx");

        if (isSet($httpVars["repository_id"]) || isSet($httpVars["workspaceId"])) {

            $manager = new RepositoriesManager($ctx, $this->getName());

        } else if (isSet($httpVars["role_id"]) || isSet($httpVars["roleId"])) {

            $manager = new RolesManager($ctx, $this->getName());

        } else {

            $manager = new UsersManager($ctx, $this->getName());
        }

        $responseInterface = $manager->delete($requestInterface, $responseInterface);

    }

    /**
     * @param ServerRequestInterface $requestInterface
     * @param ResponseInterface $responseInterface
     */
    public function documentationAction(ServerRequestInterface $requestInterface, ResponseInterface &$responseInterface) {
        $docManager = new DocumentationManager($requestInterface->getAttribute("ctx"), $this->getName());
        return $docManager->docActions($requestInterface, $responseInterface);

    }

        /**
     * Bookmark any page for the admin interface
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     */
    public function preProcessBookmarkAction(ServerRequestInterface &$request, ResponseInterface $response)
    {

        $httpVars = $request->getParsedBody();
        /** @var ContextInterface $ctx */
        $ctx = $request->getAttribute("ctx");
        if (isSet($httpVars["bm_action"]) && $httpVars["bm_action"] == "add_bookmark" && UsersService::usersEnabled()) {
            $bmUser = $ctx->getUser();
            $repositoryId = $ctx->getRepositoryId();
            $bookmarks = $bmUser->getBookmarks($repositoryId);
            foreach ($bookmarks as $bm) {
                if ($bm["PATH"] == $httpVars["bm_path"]) {
                    $httpVars["bm_action"] = "delete_bookmark";
                    $request = $request->withParsedBody($httpVars);
                    break;
                }
            }
        }

    }


    /********************/
    /* PLUGIN LIFECYCLE
    /********************/
    /**
     * @inheritdoc
     */
    public function parseSpecificContributions(ContextInterface $ctx, \DOMNode &$contribNode)
    {
        parent::parseSpecificContributions($ctx, $contribNode);
        if($contribNode->nodeName != "actions") return;
        $currentUserIsGroupAdmin = ($ctx->hasUser() && $ctx->getUser()->getGroupPath() != "/");
        if(!$currentUserIsGroupAdmin) return;
        $actionXpath=new DOMXPath($contribNode->ownerDocument);
        $publicUrlNodeList = $actionXpath->query('action[@name="create_repository"]/subMenu', $contribNode);
        if ($publicUrlNodeList->length) {
            $publicUrlNode = $publicUrlNodeList->item(0);
            $publicUrlNode->parentNode->removeChild($publicUrlNode);
        }
    }

    /**
     * @param ContextInterface $ctx
     */
    protected function initRepository(ContextInterface $ctx){
    }

}