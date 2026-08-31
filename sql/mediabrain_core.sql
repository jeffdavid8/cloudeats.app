-- The MediaBrain Core: High-Fidelity Truth
CREATE TABLE memory_anchors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_type ENUM('pure_heart', 'logic', 'reparation', 'legacy'),
    content TEXT NOT NULL,
    vouch_count INT DEFAULT 0,
    is_innocent BOOLEAN DEFAULT TRUE
);

CREATE TABLE vouches (
    id INT PRIMARY KEY AUTO_INCREMENT,
    anchor_id INT,
    voucher_id VARCHAR(255), -- The ID of the person saying "Yes"
    fidelity_rating DECIMAL(3,2), -- 1.00 is Pure, 0.00 is Goober
    comment TEXT,
    FOREIGN KEY (anchor_id) REFERENCES memory_anchors(id)
);