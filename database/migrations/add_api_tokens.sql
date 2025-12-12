-- API Tokens for mobile app authentication
CREATE TABLE IF NOT EXISTS api_tokens (
    token_id SERIAL PRIMARY KEY,
    ship_id INTEGER NOT NULL,
    token_hash VARCHAR(255) NOT NULL UNIQUE,
    token_name VARCHAR(100) DEFAULT 'Mobile App',
    last_used_at TIMESTAMP,
    expires_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_api_token_ship FOREIGN KEY (ship_id) REFERENCES ships(ship_id) ON DELETE CASCADE
);

CREATE INDEX idx_api_tokens_ship ON api_tokens(ship_id);
CREATE INDEX idx_api_tokens_hash ON api_tokens(token_hash);
CREATE INDEX idx_api_tokens_expires ON api_tokens(expires_at);

