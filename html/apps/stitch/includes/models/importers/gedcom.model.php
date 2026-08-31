<?php
class GedcomImporter
{
  public function canHandle($extension)
  {
    return $extension === 'ged';
  }

  public function parse($content)
  {
    $content = str_replace("\r", "", $content);
    $lines = explode("\n", $content);

    $results = [];
    $current = null;
    $currentEvent = null;

    foreach ($lines as $line) {
      $line = trim($line);
      if (empty($line)) continue;

      // 1. Detect New Individual
      if (preg_match('/0 @([^@]+)@ INDI/', $line, $matches)) {
        if ($current) $results[] = $current;

        $current = [
          'gedcom_id' => $matches[1],
          'name' => 'Unknown',
          'date' => '1800-01-01 12:00:00',
          'location' => '',
          'content_type' => 'ancestory',
          'all_events' => []
        ];
        $currentEvent = null;
        continue;
      }

      if (!$current) continue;

      // 2. Keep original GEDCOM tags as keys (BIRT, DEAT, etc.)
      if (preg_match('/1 (BIRT|DEAT|MARR|RESI|GRAD|OCCU)/', $line, $m)) {
        $currentEvent = $m[1];
        $current['all_events'][$currentEvent] = [
          'tag' => $currentEvent, // Keep the original tag!
          'DATE' => null,         // Use GEDCOM sub-tag names
          'PLAC' => null
        ];
        continue;
      }

      // 3. Capture Name
      if (preg_match('/1 NAME (.*)/', $line, $m)) {
        $current['name'] = trim(str_replace('/', '', $m[1]));
      }

      // 4. Capture Sub-tags using original GEDCOM keys
      if (preg_match('/2 DATE (.*)/', $line, $m)) {
        $rawDate = trim($m[1]);
        if ($currentEvent) {
          $current['all_events'][$currentEvent]['DATE'] = $rawDate;
        }
        // Logic for the primary timeline "Era"
        if ($currentEvent === 'BIRT' || $current['date'] === '1800-01-01 12:00:00') {
          $ts = strtotime($rawDate);
          if ($ts) {
            $current['date'] = date('Y-m-d H:i:s', $ts);
          }
        }
      }

      if (preg_match('/2 PLAC (.*)/', $line, $m)) {
        $place = trim($m[1]);
        if ($currentEvent) {
          $current['all_events'][$currentEvent]['PLAC'] = $place;
        }
        if ($currentEvent === 'BIRT' || empty($current['location'])) {
          $current['location'] = $place;
        }
      }
    }

    if ($current) $results[] = $current;
    return $results;
  }

  public function parseNexus($content)
  {
    $content = str_replace("\r", "", $content);
    $lines = explode("\n", $content);

    $families = [];
    $currentFam = null;

    foreach ($lines as $line) {
      $line = trim($line);

      // 1. Detect a Family Record Start (e.g., 0 @F1@ FAM)
      if (preg_match('/0 @([^@]+)@ FAM/', $line, $matches)) {
        if ($currentFam) $families[] = $currentFam;
        $currentFam = [
          'id' => $matches[1],
          'parents' => [],
          'children' => []
        ];
        continue;
      }

      if (!$currentFam) continue;

      // 2. Capture Husband or Wife (Parents)
      if (preg_match('/1 (HUSB|WIFE) @([^@]+)@/', $line, $m)) {
        $currentFam['parents'][] = $m[2]; // e.g., P3 or P5
      }

      // 3. Capture Children
      if (preg_match('/1 CHIL @([^@]+)@/', $line, $m)) {
        $currentFam['children'][] = $m[1]; // e.g., P1
      }
    }

    if ($currentFam) $families[] = $currentFam;

    // 4. Translate Families into Nexus Links
    $nexusLinks = [];
    foreach ($families as $fam) {
      foreach ($fam['parents'] as $parent) {
        foreach ($fam['children'] as $child) {
          $nexusLinks[] = [
            'stitch_id' => $parent,
            'nexus_id' => $child,
            'type' => 'parent_child',
            'weight' => 1.0
          ];
        }
      }
    }

    return $nexusLinks;
  }
}
