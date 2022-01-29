<?php

namespace App\Controllers;

use App\Plugins\Http\Response as Status;
use App\Plugins\Http\Exceptions;

class FacilityController extends BaseController
{
    function __construct() {
        $this->body = json_decode(file_get_contents('php://input'), true);
        $this->queryParams = [];

        parse_str($_SERVER['QUERY_STRING'], $this->queryParams);
    }

    public function create() {
        try {
            $this->db->beginTransaction();
            $tags = $this->body["tags"];
        
            $this->db->executeQuery("INSERT INTO `facilities` (name, location_id) VALUES (?, ?)", [$this->body["name"], $this->body["location_id"]]);
            $facilityId = $this->db->getLastInsertedId();
    
            $this->createTags($tags, $facilityId);
            $this->db->commit();

            return (new Status\Created(['id' => $facilityId]))->send();
        } catch (\PDOException $e) {
            $this->db->rollBack();
            return (new Status\InternalServerError(["error" => $e]))->send();
        }
    }

    public function read($id = null) {
        if ($id !== null) {
            $query = $this->db->executeQuery("
                SELECT 
                    facilities.id AS 'facility_id', 
                    facilities.name,
                    facilities.location_id,
                    facilities.creation_date,

                    locations.city,
                    locations.address,
                    locations.zip_code,
                    locations.country_code,
                    locations.phone_number
                FROM `facilities` 
                INNER JOIN locations ON facilities.location_id = locations.id
                WHERE facilities.id = ?"
            , [$id]);
        } else {
            $query = $this->db->executeQuery("
                SELECT 
                    facilities.id AS 'facility_id', 
                    facilities.name,
                    facilities.location_id,
                    facilities.creation_date,

                    locations.city,
                    locations.address,
                    locations.zip_code,
                    locations.country_code,
                    locations.phone_number
                FROM `facilities` 
                INNER JOIN locations ON facilities.location_id = locations.id"
            , []);
        }

        $data = [];
        while ($rawRow = $query->fetch()) {
            $row = [];

            $row["id"] = $rawRow["facility_id"];
            $row["name"] = $rawRow["name"];
            $row["creation_date"] = $rawRow["creation_date"];

            $row["location"] = array(
                "id" => $rawRow["location_id"],
                "city" => $rawRow["city"],
                "address" => $rawRow["address"],
                "zip_code" => $rawRow["zip_code"],
                "country_code" => $rawRow["country_code"],
                "phone_number" => $rawRow["phone_number"]
            );
            $row["tags"] = $this->getTags($rawRow["facility_id"]);
            $data["facilities"][] = $row;
        }

        if (!empty($data)) {
            return (new Status\Ok($data))->send();
        } else {
            return (new Status\NotFound(["error" => "given id doesnt belong to any facility"]))->send();
        }
    }

    public function update($id) {
        $facility = $this->db->executeQuery("SELECT * FROM `facilities` WHERE id = ?", [$id])->fetch();
        if ($facility !== false) {
            try {
                $this->db->beginTransaction();
                $this->db->executeQuery("UPDATE `facilities` SET name = ?, location_id = ? WHERE `facilities`.`id` = ?", [$this->body["name"], $this->body["location_id"], $id]);
            
                $newTags = $this->body["tags"];
                $currentTags = $this->getTags($id);

                foreach ($currentTags as $tag) {
                    $search = array_search($tag, $newTags);

                    // Previous existing tag was not found in the list of new ones, delete it.
                    if ($search == false) {
                        $tagId = $this->getTag($tag)["id"];
                        $this->db->executeQuery("DELETE FROM `facility_tags` WHERE facility_id = ? AND tag_id = ?", [$id, $tagId])->fetch();
                    } else {
                        // Previous existing tag was found, but it should not be re-added. Delete the item from the new array.
                        unset($newTags[$search]);
                    }
                }

                // Add or Edit tags
                $this->createTags($newTags, $id);
                $this->db->commit();

                return (new Status\NoContent())->send();
            } catch (\PDOException $e) {
                $this->db->rollBack();
                return (new Status\InternalServerError(["error" => $e]))->send();
            }
        } else {
            return (new Status\NotFound(["error" => "given id doesnt belong to any facility"]))->send();
        }
    }

    public function delete($id) {
        $facility = $this->db->executeQuery("SELECT * FROM `facilities` WHERE id = ?", [$id])->fetch();
        if ($facility !== false) {
            $this->db->executeQuery("DELETE FROM `facilities` WHERE id = ?", [$id]);
            $this->db->executeQuery("DELETE FROM `facility_tags` WHERE facility_id = ?", [$id])->fetch();
            return (new Status\NoContent())->send();
        } else {
            return (new Status\NotFound(["error" => "given id doesnt belong to any facility"]))->send();
        }
    }

    public function search() {
        $criteria = $this->queryParams["criteria"];
        $dbMatches = $this->db->executeQuery("
            (SELECT 'tag_id' id_type, id, name FROM `tags` WHERE `name` LIKE '%" . $criteria . "%')
                UNION
            (SELECT 'facility_id', id, name FROM `facilities` WHERE `name` LIKE '%" . $criteria . "%')
                UNION
            (SELECT 'location_id', id, city FROM `locations` WHERE `city` LIKE '%" . $criteria . "%')
        ", [])->fetchAll();
        
        $data = [];
        foreach ($dbMatches as $match) {
            switch ($match["id_type"]) {
                case "location_id":
                    $matches = $this->db->executeQuery("SELECT * FROM `facilities` WHERE location_id = ?", [$match["id"]])->fetchAll();
                    foreach ($matches as $innerMatch) {
                        $data["location"][] = $innerMatch;
                    }
                    break;

                case "facility_id":
                    $data["facility"][] = $this->db->executeQuery("SELECT * FROM `facilities` WHERE id = ?", [$match["id"]])->fetch();
                    break;

                case "tag_id":
                    $matches = $this->db->executeQuery("SELECT * FROM `facility_tags` WHERE tag_id = ?", [$match["id"]])->fetchAll();
                    foreach ($matches as $innerMatch) {
                        $data["tag"][] = $this->db->executeQuery("SELECT * FROM `facilities` WHERE id = ?", [$innerMatch["facility_id"]])->fetch();
                    }
                    break;
            }
        }

        return (new Status\Ok(['results' => $data]))->send();
    }

    public function getTag($bind) {
        if (is_numeric($bind)) {
            return $this->db->executeQuery("SELECT * FROM `tags` WHERE id = ?", [$bind])->fetch();
        } else {
            return $this->db->executeQuery("SELECT * FROM `tags` WHERE name = ?", [$bind])->fetch();
        }
    }

    public function getTags($id) {
        $query = $this->db->executeQuery("
            SELECT * FROM `facility_tags` 
            INNER JOIN tags ON facility_tags.tag_id = tags.id 
            WHERE facility_id = ?;"
        , [$id]);
        $tags = [];
        while ($row = $query->fetch()) {
            $tags[] = $row["name"];
        }
        return $tags;
    }

    public function createTags($tags, $id) {
        foreach ($tags as $tag) {
            try {
                $this->db->executeQuery("INSERT INTO `tags` (name) VALUES (?)", [$tag]);
                $tagId = $this->db->getLastInsertedId();
            } catch (\PDOException $e) {
                // Tags are unique, each one that isn't we just grab its ID. 
                $tagId = $this->getTag($tag)["id"];
            } finally {
                $this->db->executeQuery("INSERT INTO `facility_tags` (facility_id, tag_id) VALUES (?, ?)", [$id, $tagId]);
            }
        }
    }
}