<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_bigbluebuttonbn\local\proxy;

use cache;
use cache_helper;
use mod_bigbluebuttonbn\local\connection;
use mod_bigbluebuttonbn\local\connection_resolver;
use SimpleXMLElement;

/**
 * The recording proxy.
 *
 * This class acts as a proxy between Moodle and the BigBlueButton API server,
 * and deals with all requests relating to recordings.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David  (laurent [at] call-learning [dt] fr)
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class recording_proxy extends proxy_base {

    /**
     * Invalidate the MUC cache for the specified recording.
     *
     * @param string $recordid
     * @param connection|null $connection server the recording is on; by default, the server of its activity
     */
    protected static function invalidate_cache_for_recording(string $recordid, ?connection $connection = null): void {
        cache_helper::invalidate_by_event(
            'mod_bigbluebuttonbn/recordingchanged',
            [self::get_cache_key($recordid, $connection)]
        );
    }

    /**
     * The server a recording is on, for callers that did not give one.
     *
     * @param string $recordingid
     * @return connection
     */
    protected static function get_recording_connection(string $recordingid): connection {
        return self::group_by_recording_server([$recordingid])[0]['connection'];
    }

    /**
     * Group recording ids by the server they are on.
     *
     * A recording is on the server of the activity it was recorded in, as currently configured for that
     * activity. Imported copies are ignored because they point to the activity the recording was imported
     * into. Recordings that are not known on this site, and sites without per-activity servers, use the
     * site server.
     *
     * @param array $recordingids
     * @return array[] groups, each with a 'connection' and the 'ids' on that server
     */
    protected static function group_by_recording_server(array $recordingids): array {
        global $DB;

        $recordingids = array_values(array_unique(array_map('strval', $recordingids)));
        $instanceids = [];
        foreach (array_chunk($recordingids, 500) as $chunk) {
            [$insql, $params] = $DB->get_in_or_equal($chunk, SQL_PARAMS_NAMED);
            $records = $DB->get_recordset_select(
                'bigbluebuttonbn_recordings',
                "recordingid {$insql} AND imported = 0",
                $params,
                'id',
                'id, recordingid, bigbluebuttonbnid'
            );
            foreach ($records as $record) {
                $instanceids[$record->recordingid] ??= (int) $record->bigbluebuttonbnid;
            }
            $records->close();
        }

        $groups = [];
        $connections = [];
        foreach ($recordingids as $recordingid) {
            $instanceid = $instanceids[$recordingid] ?? 0;
            if (!array_key_exists($instanceid, $connections)) {
                $connections[$instanceid] = $instanceid
                    ? connection_resolver::for_instance($instanceid)
                    : connection_resolver::site();
            }
            $connection = $connections[$instanceid];
            $key = sha1(implode("\n", [$connection->serverurl, $connection->sharedsecret, $connection->checksumalgorithm]));
            $groups[$key]['connection'] = $connection;
            $groups[$key]['ids'][] = $recordingid;
        }
        return array_values($groups);
    }

    /**
     * Perform deleteRecordings on BBB.
     *
     * @param string $recordid a recording id
     * @param connection|null $connection server the recording is on; by default, the server of its activity
     * @return bool
     */
    public static function delete_recording(string $recordid, ?connection $connection = null): bool {
        $connection ??= self::get_recording_connection($recordid);
        $result = self::fetch_endpoint_xml('deleteRecordings', ['recordID' => $recordid], [], null, $connection);
        if (!$result || $result->returncode != 'SUCCESS') {
            return false;
        }
        return true;
    }

    /**
     * Perform publishRecordings on BBB.
     *
     * @param string $recordid
     * @param string $publish
     * @param connection|null $connection server the recording is on; by default, the server of its activity
     * @return bool
     */
    public static function publish_recording(
        string $recordid,
        string $publish = 'true',
        ?connection $connection = null,
    ): bool {
        $connection ??= self::get_recording_connection($recordid);
        $result = self::fetch_endpoint_xml('publishRecordings', [
            'recordID' => $recordid,
            'publish' => $publish,
        ], [], null, $connection);

        self::invalidate_cache_for_recording($recordid, $connection);

        if (!$result || $result->returncode != 'SUCCESS') {
            return false;
        }

        return true;
    }

    /**
     * Perform publishRecordings on BBB.
     *
     * @param string $recordid
     * @param string $protected
     * @param connection|null $connection server the recording is on; by default, the server of its activity
     * @return bool
     */
    public static function protect_recording(
        string $recordid,
        string $protected = 'true',
        ?connection $connection = null,
    ): bool {
        global $CFG;

        // Ignore action if recording_protect_editable is set to false.
        if (empty($CFG->bigbluebuttonbn_recording_protect_editable)) {
            return false;
        }
        $connection ??= self::get_recording_connection($recordid);

        $result = self::fetch_endpoint_xml('updateRecordings', [
            'recordID' => $recordid,
            'protect' => $protected,
        ], [], null, $connection);

        self::invalidate_cache_for_recording($recordid, $connection);

        if (!$result || $result->returncode != 'SUCCESS') {
            return false;
        }

        return true;
    }

    /**
     * Perform updateRecordings on BBB.
     *
     * @param string $recordid a single record identifier
     * @param array $params ['key'=>param_key, 'value']
     * @param connection|null $connection server the recording is on; by default, the server of its activity
     * @return bool
     */
    public static function update_recording(string $recordid, array $params, ?connection $connection = null): bool {
        $connection ??= self::get_recording_connection($recordid);
        $result = self::fetch_endpoint_xml('updateRecordings', array_merge([
            'recordID' => $recordid
        ], $params), [], null, $connection);

        self::invalidate_cache_for_recording($recordid, $connection);

        return $result ? $result->returncode == 'SUCCESS' : false;
    }

    /**
     * Helper function to fetch a single recording from a BigBlueButton server.
     *
     * @param string $recordingid
     * @param connection|null $connection server the recording is on; by default, the server of its activity
     * @return null|array
     */
    public static function fetch_recording(string $recordingid, ?connection $connection = null): ?array {
        $data = self::fetch_recordings([$recordingid], $connection);

        if (array_key_exists($recordingid, $data)) {
            return $data[$recordingid];
        }

        return null;
    }

    /**
     * Check whether the current recording is a protected recording and purge the cache if necessary.
     *
     * @param string $recordingid
     * @param connection|null $connection server the recording is on; by default, the server of its activity
     */
    public static function purge_protected_recording(string $recordingid, ?connection $connection = null): void {
        $connection ??= self::get_recording_connection($recordingid);
        $cache = cache::make('mod_bigbluebuttonbn', 'recordings');
        $cachekey = self::get_cache_key($recordingid, $connection);

        $recording = $cache->get($cachekey);
        if (empty($recording)) {
            // This value was not cached to begin with.
            return;
        }

        if (array_key_exists('protected', $recording) && $recording['protected'] === 'true') {
            // This item is protected. Purge it from the cache.
            $cache->delete($cachekey);
            return;
        }

        $currentfetchcache = cache::make('mod_bigbluebuttonbn', 'currentfetch');
        if ($currentfetchcache->has($cachekey)) {
            // This item was fetched in the current request.
            return;
        }
    }

    /**
     * Helper function to fetch recordings from a BigBlueButton server.
     *
     * We use a cache to store recording indexed by keyids/recordingID.
     * @param array $keyids list of recordingids
     * @param connection|null $connection server the recording is on; by default, the server of its activity
     * @return array (associative) with recordings indexed by recordID, each recording is a non sequential array
     *  and sorted by {@see recording_proxy::sort_recordings}
     */
    public static function fetch_recordings(array $keyids = [], ?connection $connection = null): array {
        $recordings = [];

        // If $ids is empty return array() to prevent a getRecordings with meetingID and recordID set to ''.
        if (empty($keyids)) {
            return $recordings;
        }
        if ($connection === null) {
            // Ask the server of each recording's activity.
            foreach (self::group_by_recording_server($keyids) as $group) {
                $recordings += self::fetch_recordings($group['ids'], $group['connection']);
            }
            return $recordings;
        }
        $cache = cache::make('mod_bigbluebuttonbn', 'recordings');
        $currentfetchcache = cache::make('mod_bigbluebuttonbn', 'currentfetch');

        // Cache keys are the recording ids for the site server, and are prefixed for any other server.
        $keyids = array_values($keyids);
        $cachekeys = array_map(fn($keyid) => self::get_cache_key((string) $keyid, $connection), $keyids);
        $cached = $cache->get_many($cachekeys);
        foreach ($cachekeys as $index => $cachekey) {
            if (!empty($cached[$cachekey])) {
                $recordings[$keyids[$index]] = $cached[$cachekey];
            }
        }
        $missingkeys = array_diff($keyids, array_keys($recordings));

        $recordings += self::do_fetch_recordings($missingkeys, 'recordID', $connection);

        $tocache = [];
        foreach ($recordings as $recordingid => $recording) {
            $tocache[self::get_cache_key((string) $recordingid, $connection)] = $recording;
        }
        $cache->set_many($tocache);
        $currentfetchcache->set_many(array_flip(array_keys($tocache)));
        return $recordings;
    }

    /**
     * Helper function to retrieve recordings that failed to be fetched from a BigBlueButton server.
     *
     * @param array $keyids list of recordingids
     * @param connection|null $connection server the recording is on; by default, the server of its activity
     * @return array array of recording recordingids not fetched from server
     *  and sorted by {@see recording_proxy::sort_recordings}
     */
    public static function fetch_missing_recordings(array $keyids = [], ?connection $connection = null): array {
        $unfetchedids = [];
        $pagesize = 25;
        // If $ids is empty return array() to prevent a getRecordings with meetingID and recordID set to ''.
        if (empty($keyids)) {
            return $unfetchedids;
        }
        if ($connection === null) {
            // Ask the server of each recording's activity.
            foreach (self::group_by_recording_server($keyids) as $group) {
                $unfetchedids = array_merge($unfetchedids, self::fetch_missing_recordings($group['ids'], $group['connection']));
            }
            return $unfetchedids;
        }
        while ($ids = array_splice($keyids, 0, $pagesize)) {
            // We make getRecordings API call to check recordings are successfully retrieved.
            $xml = self::fetch_endpoint_xml(
                'getRecordings',
                ['recordID' => implode(',', $ids), 'state' => 'any'],
                [],
                null,
                $connection
            );
            if (!$xml || $xml->returncode != 'SUCCESS' || !isset($xml->recordings)) {
                $unfetchedids = array_merge($unfetchedids, $ids);
                continue; // We will keep record of all unfetched ids.
            }
        }
        return $unfetchedids;
    }

    /**
     * Helper function to fetch recordings from a BigBlueButton server.
     *
     * @param array $keyids list of meetingids
     * @param connection|null $connection server the meetings were held on, the site one by default
     * @return array (associative) with recordings indexed by recordID, each recording is a non sequential array
     *  and sorted by {@see recording_proxy::sort_recordings}
     */
    public static function fetch_recording_by_meeting_id(array $keyids = [], ?connection $connection = null): array {
        $recordings = [];

        // If $ids is empty return array() to prevent a getRecordings with meetingID and recordID set to ''.
        if (empty($keyids)) {
            return $recordings;
        }
        $recordings = self::do_fetch_recordings($keyids, 'meetingID', $connection);
        return $recordings;
    }

    /**
     * Helper function to fetch recordings from a BigBlueButton server.
     *
     * @param array $keyids list of meetingids or recordingids
     * @param string $key the param name used for the BBB request (<recordID>|meetingID)
     * @param connection|null $connection server to ask, the site one by default
     * @return array (associative) with recordings indexed by recordID, each recording is a non sequential array.
     *  and sorted {@see recording_proxy::sort_recordings}
     */
    private static function do_fetch_recordings(
        array $keyids = [],
        string $key = 'recordID',
        ?connection $connection = null,
    ): array {
        $recordings = [];
        $pagesize = 25;
        while ($ids = array_splice($keyids, 0, $pagesize)) {
            $fetchrecordings = self::fetch_recordings_page($ids, $key, $connection);
            $recordings += $fetchrecordings;
        }
        // Sort recordings.
        return self::sort_recordings($recordings);
    }
    /**
     * Helper function to fetch a page of recordings from the remote server.
     *
     * @param array $ids
     * @param string $key
     * @param connection|null $connection server to ask, the site one by default
     * @return array
     */
    private static function fetch_recordings_page(array $ids, $key = 'recordID', ?connection $connection = null): array {
        // The getRecordings call is executed using a method GET (supported by all versions of BBB).
        $xml = self::fetch_endpoint_xml(
            'getRecordings',
            [$key => implode(',', $ids), 'state' => 'any'],
            [],
            null,
            $connection
        );

        if (!$xml) {
            return [];
        }

        if ($xml->returncode != 'SUCCESS') {
            return [];
        }

        if (!isset($xml->recordings)) {
            return [];
        }

        $recordings = [];
        // If there were recordings already created.
        foreach ($xml->recordings->recording as $recordingxml) {
            $recording = self::parse_recording($recordingxml);
            $recordings[$recording['recordID']] = $recording;
            // Check if there are any child.
            if (isset($recordingxml->breakoutRooms->breakoutRoom)) {
                $breakoutrooms = [];
                foreach ($recordingxml->breakoutRooms->breakoutRoom as $breakoutroom) {
                    $breakoutrooms[] = trim((string) $breakoutroom);
                }
                if ($breakoutrooms) {
                    $xml = self::fetch_endpoint_xml(
                        'getRecordings',
                        ['recordID' => implode(',', $breakoutrooms)],
                        [],
                        null,
                        $connection
                    );
                    if ($xml && $xml->returncode == 'SUCCESS' && isset($xml->recordings)) {
                        // If there were already created meetings.
                        foreach ($xml->recordings->recording as $subrecordingxml) {
                            $recording = self::parse_recording($subrecordingxml);
                            $recordings[$recording['recordID']] = $recording;
                        }
                    }
                }
            }
        }

        return $recordings;
    }

    /**
     *  Helper function to sort an array of recordings. It compares the startTime in two recording objects.
     *
     * @param array $recordings
     * @return array
     */
    public static function sort_recordings(array $recordings): array {
        global $CFG;

        uasort($recordings, function($a, $b) {
            if ($a['startTime'] < $b['startTime']) {
                return -1;
            }
            if ($a['startTime'] == $b['startTime']) {
                return 0;
            }
            return 1;
        });

        return $recordings;
    }

    /**
     * Helper function to parse an xml recording object and produce an array in the format used by the plugin.
     *
     * @param SimpleXMLElement $recording
     *
     * @return array
     */
    public static function parse_recording(SimpleXMLElement $recording): array {
        // Add formats.
        $playbackarray = [];
        foreach ($recording->playback->format as $format) {
            $playbackarray[(string) $format->type] = [
                'type' => (string) $format->type,
                'url' => trim((string) $format->url), 'length' => (string) $format->length
            ];
            // Add preview per format when existing.
            if ($format->preview) {
                $playbackarray[(string) $format->type]['preview'] =
                    self::parse_preview_images($format->preview);
            }
        }
        // Add the metadata to the recordings array.
        $metadataarray =
            self::parse_recording_meta(get_object_vars($recording->metadata));
        $recordingarray = [
            'recordID' => (string) $recording->recordID,
            'meetingID' => (string) $recording->meetingID,
            'meetingName' => (string) $recording->name,
            'published' => (string) $recording->published,
            'state' => (string) $recording->state,
            'startTime' => (string) $recording->startTime,
            'endTime' => (string) $recording->endTime,
            'playbacks' => $playbackarray
        ];
        if (isset($recording->protected)) {
            $recordingarray['protected'] = (string) $recording->protected;
        }
        return $recordingarray + $metadataarray;
    }

    /**
     * Helper function to convert an xml recording metadata object to an array in the format used by the plugin.
     *
     * @param array $metadata
     *
     * @return array
     */
    public static function parse_recording_meta(array $metadata): array {
        $metadataarray = [];
        foreach ($metadata as $key => $value) {
            if (is_object($value)) {
                $value = '';
            }
            $metadataarray['meta_' . $key] = $value;
        }
        return $metadataarray;
    }

    /**
     * Helper function to convert an xml recording preview images to an array in the format used by the plugin.
     *
     * @param SimpleXMLElement $preview
     *
     * @return array
     */
    public static function parse_preview_images(SimpleXMLElement $preview): array {
        $imagesarray = [];
        foreach ($preview->images->image as $image) {
            $imagearray = ['url' => trim((string) $image)];
            foreach ($image->attributes() as $attkey => $attvalue) {
                $imagearray[$attkey] = (string) $attvalue;
            }
            array_push($imagesarray, $imagearray);
        }
        return $imagesarray;
    }
}
