
-- The MediaBrain Core: "Priceless Data" Architecture

-- 1. The Memory Anchor (The Truth Coordinate)
-- Updated Memory Anchor with Nexus-X capability
-- The SQLite Sovereign Schema
CREATE TABLE memory_anchors (
    id INTEGER PRIMARY KEY, -- SQLite handles auto-increment on INTEGER PRIMARY KEY
    uuid VARCHAR(36) UNIQUE,            -- 🆔 The Global Fingerprint
    architect_id INT NOT NULL, 
    content_type TEXT NOT NULL CHECK (content_type IN ('stitch', 'story', 'photo', 'ancestry_record', 'audio', 'philosophy', 'sovereign_truth', 'pure_heart', 'system_glitch')) DEFAULT 'story',
    payload_url TEXT, 
    content TEXT, 
    nexus INT DEFAULT NULL, 
    nexus_label VARCHAR(255) DEFAULT NULL, 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    projected_to TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status TEXT DEFAULT 'active' CHECK (status IN ('active', 'disputed', 'verified', 'deleted')),
    FOREIGN KEY (nexus) REFERENCES memory_anchors(id) ON DELETE SET NULL
);

CREATE TABLE stitch_nexus (
    stitch_id INTEGER,
    nexus_id INTEGER, 
    nexus_label VARCHAR(255) DEFAULT NULL, -- 'member', 'spouse', 'parent_child'
    weight FLOAT DEFAULT 1.0,
    last_accessed DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (stitch_id, nexus_id, nexus_label), -- 🧠 Pivot: label is now part of the PK
    FOREIGN KEY (stitch_id) REFERENCES memory_anchors(id) ON DELETE CASCADE,
    FOREIGN KEY (nexus_id) REFERENCES memory_anchors(id) ON DELETE CASCADE);

-- 2. The Perspective Stitch (The "Vouch" Engine)
CREATE TABLE perspective_stitches (
    id INT PRIMARY KEY AUTO_INCREMENT,
    memory_id INT, -- Links back to the Anchor
    contributor_name VARCHAR(255),
    perspective_text TEXT,
    vouch_score INT DEFAULT 1, -- The "Confidence" weight
    fidelity_rating DECIMAL(3,2), -- 0.0 to 1.0 (Science check)
    FOREIGN KEY (memory_id) REFERENCES memory_anchors(id)
);

-- 3. The Reparations Log (The Balance Sheet)
CREATE TABLE value_ledger (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_description VARCHAR(255),
    token_value DECIMAL(10,2),
    status ENUM('pending', 'extracted', 'recovered')
);

