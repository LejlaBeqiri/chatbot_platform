# 🧠 Business Chatbot Platform with RAG  

This project is a **platform that enables businesses to create and personalize AI-powered chatbots** using their own documents and data.  
It was developed as part of my thesis, showcasing how **Retrieval-Augmented Generation (RAG)** can be applied in real-world business environments.  

The system allows companies to upload knowledge sources (PDF/JSONL), which are processed into **embeddings** and stored in a vector database.  
When customers interact with the chatbot, it retrieves the most relevant information and generates **accurate, context-aware answers** using OpenAI models.  

---

## ✨ Features  
- 👥 **Multi-role system**: Admin and Tenant (business).  
- 📄 **Knowledge upload**: Supports **PDF** and **JSONL** files.  
- 🔎 **Semantic search**: Uses PostgreSQL with **pgvector** for embeddings.  
- 🤖 **Personalized chatbots**: Businesses build their own knowledge bases.  
- 🌐 **SDK integration**: Chatbots can be embedded into external websites.  
- ⚡ **Fast retrieval**: Powered by **HNSW indexing** for efficient similarity search.  

---

## 🏗️ Architecture  
- **Backend**: Laravel (APIs, authentication, queues, embeddings)  
- **Frontend**: Vue.js (tenant dashboards, chatbot UI)  
- **Database**: PostgreSQL + pgvector  
- **AI Models**: OpenAI  
  - `text-embedding-3-small` → for embeddings  
  - `gpt-4o-mini` → for response generation  
- **Security**: Laravel Sanctum, Policies  
- **Background Jobs**: Laravel Queues  

---

## ⚙️ How It Works  
1. 📂 Businesses upload documents (PDF/JSONL).  
2. 🧩 Content is processed into embeddings (1536-dim vectors).  
3. 🗄️ Vectors are stored in PostgreSQL with `pgvector`.  
4. 🔎 A user query is embedded and compared to stored vectors (cosine similarity + HNSW).  
5. 📝 The most relevant context is retrieved.  
6. 🤖 GPT-4o-mini generates a context-aware, personalized response.  

---

## 📊 Example Use Case  
**Airline Agency**  
- Uploads policies, FAQs, and travel guidelines.  
- Customers ask about baggage rules, flight changes, or refunds.  
- The chatbot retrieves accurate info from the uploaded docs and responds instantly.  

---

## 📚 Thesis Context  
This project was developed as part of my **Bachelor’s/Master’s thesis**, demonstrating how **RAG (Retrieval-Augmented Generation)** can bridge the gap between general-purpose LLMs and domain-specific business needs.  

---

## 🔗 Related Repositories  
- [Backend (Laravel)](https://github.com/LejlaBeqiri/chatbot-platform)  
- [Frontend (Vue.js)](https://github.com/LejlaBeqiri/chatbot-platform-frontend)  
- [Frontend SDK](https://github.com/LejlaBeqiri/chatbot-frontend-sdk)  

---

## 📌 Future Improvements  
- Multi-language chatbot support.  
- Analytics dashboard for businesses.  
- Voice-enabled chatbot.  
- Optional fine-tuning for specialized industries.  

---
