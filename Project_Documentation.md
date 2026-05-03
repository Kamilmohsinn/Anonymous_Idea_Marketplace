# Phase 1: System Overview & Requirements

## Task 01: System Overview & Brainstorming

### Project Title
**Anonymous Idea Marketplace**

### Problem Statement
Many aspiring entrepreneurs, students, and professionals have brilliant startup or project ideas but are hesitant to share them publicly. They fear idea theft or harsh public judgment. This limits collaboration and stifles innovation since great concepts remain hidden and underdeveloped due to a lack of safe environments for constructive feedback.

### Proposed Solution Summary
The Anonymous Idea Marketplace is a secure web platform designed to foster innovation by allowing users to share their startup ideas anonymously. By utilizing pseudonyms, creators can receive genuine feedback, community voting, and collaboration requests without risking their direct identity. The platform includes a robust ranking algorithm to highlight trending ideas and a moderated discussion system to refine concepts. This encourages a supportive community where good ideas can thrive and potential founders can team up organically.

---

## Task 02: Core Features, Modules & Actors Identification

### Actors
*   **Primary Actors:**
    *   **Registered Users (Innovators/Collaborators):** These users are the core contributors who post ideas anonymously, participate in discussions, upvote/downvote ideas, and send collaboration requests.
    *   **Visitors (Guests):** Unregistered users who can browse, search, and view trending ideas but cannot interact or post.
*   **Secondary Actors:**
    *   **Administrators:** Responsible for platform moderation, managing reported ideas/comments, and ensuring the community guidelines are followed.

### Key Features
1.  **Anonymous Posting & Pseudonyms:** Real names stay hidden behind system-generated aliases (e.g., Innovator_432) when posting ideas.
2.  **Idea Voting & Ranking Algorithm:** A dynamic ranking system prioritizing ideas based on a score derived from upvotes, comments, and collaboration requests.
3.  **Private Collaboration Requests:** A secure mechanism for interested parties to request directly teaming up with an anonymous idea creator.
4.  **Discussion & Comment Threads:** An interactive section under each idea for constructive feedback and debate.
5.  **Idea Discovery & Filtering:** Advanced search with filtering by tags, categories, and trending status.
6.  **Progress Tracking:** Idea creators can update the stage of their idea (e.g., Concept, Prototype, Startup).
7.  **Idea Similarity Detection:** Mechanism to inform users if similar concepts already exist upon submission.
8.  **Reputation System:** Users earn internal points for valuable contributions (highly-rated ideas or helpful comments).

### Main Modules

1.  **User Authentication Module**
    *   **Purpose:** Securely handles user registration, login, and profile management while ensuring their identity remains strictly protected from public view.
    *   **Used By:** Registered Users, Administrators.
2.  **Idea Posting & Anonymous Identity Module**
    *   **Purpose:** Allows users to submit detailed startup ideas (with problem/solution statements and tags) and assigns them unique, random pseudonyms for that specific post.
    *   **Used By:** Registered Users.
3.  **Voting, Ranking & Trending Algorithm Module**
    *   **Purpose:** Manages user votes and calculates a "Trending Score" to order the ideas on the platform (New, Top, Trending).
    *   **Used By:** All Actors (Visitors view the rankings, Registered Users cast votes).
4.  **Collaboration Request & Messaging Module**
    *   **Purpose:** Enables users to initiate contact for teamwork. If the creator accepts, a private communication channel opens.
    *   **Used By:** Registered Users.
5.  **Discussion / Comment Module**
    *   **Purpose:** Facilitates threaded conversations under each idea for structured feedback.
    *   **Used By:** Registered Users.
6.  **Reporting & Moderation Module**
    *   **Purpose:** Provides tools to deal with spam, inappropriate content, and managing accounts to maintain platform integrity.
    *   **Used By:** Registered Users (to report), Administrators (to act).

---

## Task 03: Screen / Interface Planning

### 1. Landing / Home Page
*   **Information Displayed:** Platform introduction, a list of top "Trending Ideas" with their titles and short descriptions, and categories.
*   **User Actions:** Browse ideas, filter by category, click an idea to read more, sign up, or log in.

### 2. Login & Registration Page
*   **Information Displayed:** Input fields for email, password, and (for registration) confirmation password. Brief reassuring text about the anonymity guarantee.
*   **User Actions:** Authenticate an existing account, create a new account, or recover a forgotten password.

### 3. User Dashboard
*   **Information Displayed:** A private overview of the user's submitted ideas, current reputation points, recent notifications, and saved/favorited ideas.
*   **User Actions:** Initiate the "Post New Idea" flow, manage existing ideas (update progress or delete), and read collaboration requests.

### 4. Idea Details Page
*   **Information Displayed:** Full description of a specific idea (problem, proposed solution, category, tags), the creator's pseudonym, current vote count, and the comment thread.
*   **User Actions:** Upvote/downvote, add a comment, reply to a comment, and click "Request Collaboration."

### 5. Post New Idea Page
*   **Information Displayed:** Form fields for Title, Category, problem statement, proposed solution, tags, and file attachments.
*   **User Actions:** Submit the idea for public viewing, save as a draft, or cancel.

### 6. Admin Panel
*   **Information Displayed:** Statistical dashboard of platform activity, list of reported ideas/users, and moderation queues.
*   **User Actions:** Delete inappropriate ideas, warn/ban users, and manage platform categories.
