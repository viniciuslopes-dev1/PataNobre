-- Selecione o banco criado no phpMyAdmin antes de importar.
CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  usuario VARCHAR(50) NOT NULL UNIQUE,
  senha VARCHAR(255) NOT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE agendamentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome_tutor VARCHAR(100) NOT NULL,
  nome_pet VARCHAR(100) NOT NULL,
  servico VARCHAR(100) NOT NULL,
  data_hora DATETIME NOT NULL,
  status ENUM('Agendado','Confirmado','Concluído','Cancelado') NOT NULL DEFAULT 'Agendado',
  observacoes TEXT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
