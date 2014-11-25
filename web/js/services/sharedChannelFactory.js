/**
 * @file
 * Contains the shared channel factory.
 */

/**
 * Shared Channel factory. Main entry point for accessing shared channels.
 */
ikApp.factory('sharedChannelFactory', ['$http', '$q', 'sharedSearchFactory',
  function($http, $q, sharedSearchFactory) {
    var factory = {};

    /**
     * Search via share Factory.
     * @param search
     * @returns {*|Number}
     */
    factory.searchChannels = function(search, indexName) {
      search.type = 'Indholdskanalen\\MainBundle\\Entity\\Channel';
      return sharedSearchFactory.search(search, indexName);
    };

    /**
     * Get the available sharing indexes.
     * @returns array of sharing indexes.
     */
    factory.getSharingIndexes = function() {
      var defer = $q.defer();

      $http.get('/api/sharing/indexes')
        .success(function(data) {
          defer.resolve(data);
        })
        .error(function(data, status) {
          defer.reject(status);
        });

      return defer.promise;
    };

    return factory;
  }
]);
