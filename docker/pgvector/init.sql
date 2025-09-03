-- Create the main database
CREATE DATABASE chatbot_platform;

-- Create the test database
CREATE DATABASE chatbot_platform_test;

-- Connect to chatbot_platform and enable vector extension
\connect chatbot_platform
CREATE EXTENSION IF NOT EXISTS vector;

-- Connect to chatbot_platform_test and enable vector extension
\connect chatbot_platform_test
CREATE EXTENSION IF NOT EXISTS vector;