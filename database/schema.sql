CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(254) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE phrases (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  frase TEXT NOT NULL,
  idioma_frase ENUM('pt-BR', 'en-GB') NOT NULL,
  descricao TEXT NOT NULL,
  idioma_descricao ENUM('pt-BR', 'en-GB') NOT NULL,
  id_user BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_phrases_user FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_phrases_user_created (id_user, created_at, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE exercises (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_phrase BIGINT UNSIGNED NOT NULL,
  frase_exercicio TEXT NOT NULL,
  resposta TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_exercises_phrase FOREIGN KEY (id_phrase) REFERENCES phrases(id) ON DELETE CASCADE,
  INDEX idx_exercises_phrase_created (id_phrase, created_at, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_game_scores (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_user BIGINT UNSIGNED NOT NULL,
  score INT NOT NULL DEFAULT 0,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_user_game_scores_user FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY uq_user_game_scores_user (id_user)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
