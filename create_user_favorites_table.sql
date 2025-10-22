-- Create user_favorites table
CREATE TABLE IF NOT EXISTS user_favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tree_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_tree (user_id, tree_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tree_id) REFERENCES trees(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_tree_id (tree_id),
    INDEX idx_created_at (created_at)
);
