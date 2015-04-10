/**
 * @file
 * Contains screen controller.
 */

/**
 * Screen controller. Controls the screen creation process.
 */
angular.module('ikApp').controller('ScreenController', ['$scope', '$location', '$routeParams', 'screenFactory', 'channelFactory', 'sharedChannelFactory', 'configuration', 'templateFactory',
  function ($scope, $location, $routeParams, screenFactory, channelFactory, sharedChannelFactory, configuration, templateFactory) {
    'use strict';

    $scope.sharingEnabled = configuration.sharingService.enabled;
    $scope.screen = {};
    $scope.toolbarTemplate = null;
    $scope.display = false;
    $scope.region = null;

    /**
     * Constructor.
     * Handles different settings of route parameters.
     */
    function init() {
      if (!$routeParams.id) {
        // If the ID is not set, get an empty slide.
        $scope.screen = screenFactory.emptyScreen();

        // Default to full-screen template if it exists, else pick the first available.
        templateFactory.getScreenTemplate('full-screen').then(
          function (data) {
            $scope.screen.template = data;
          },
          function (reason) {
            if (reason === 404) {
              templateFactory.getScreenTemplates().then(
                function (data) {
                  $scope.screen.template = data[0];
                },
                function (reason) {
                  // @TODO: Handle error!!
                  console.log(reason);
                }
              );
            }
          }
        );
      }
      else {
        if ($routeParams.id === null || $routeParams.id === undefined || $routeParams.id === '') {
          $location.path('/screen-overview');
        } else {
          // Get the screen from the backend.
          screenFactory.getEditScreen($routeParams.id).then(
            function (data) {
              $scope.screen = data;

              // Decode the shared channels.
              $scope.screen.channel_screen_regions.forEach(function (csr) {
                if (csr.shared_channel) {
                  // Parse the content of the shared channel
                  //   Set title and slides of the shared_channel.
                  csr.shared_channel.content = JSON.parse(csr.shared_channel.content);
                  csr.shared_channel.title = csr.shared_channel.content.title;
                  csr.shared_channel.slides = csr.shared_channel.content.slides;
                }
              });

              if ($scope.screen === {}) {
                $location.path('/screen');
              }
            },
            // Error getting
            function (reason) {
              console.log(reason);
            }
          );
        }
      }
    }

    init();

    /**
     * Save the screen.
     */
    $scope.saveScreen = function saveScreen() {
      $scope.displayToolbar = false;
      $scope.region = null;
      screenFactory.saveScreen().then(
        function () {
        },
        function () {
          // @TODO: Handle error.
        }
      );
    };

    /**
     * Trigger the tool defined in the tool parameter.
     * @param tool
     *   The tool and region to trigger.
     */
    $scope.triggerTool = function triggerTool(tool) {
      $scope.toolbarTemplate = 'app/shared/toolbars/' + tool.name + '.html';
      $scope.region = tool.region;
      $scope.displayToolbar = true;
    };
  }
]);