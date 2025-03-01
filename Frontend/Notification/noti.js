// Sample data for notifications
const notifications = [
    {
      type: "skill-request",
      user: "@annon",
      skill: "Guitar"
    },
    {
      type: "memory-reminder",
      message: "You have to submit memory to complete the learning process."
    },
    {
      type: "success-message",
      message: "Memory submitted successfully!"
    }
  ];
  
  // Function to render notifications dynamically
  function renderNotifications() {
    const notificationsSection = document.getElementById("notifications-section");
  
    notifications.forEach(notification => {
      const notificationDiv = document.createElement("div");
      notificationDiv.classList.add("notification");
  
      if (notification.type === "skill-request") {
        notificationDiv.innerHTML = `
          <div class="notification-content">
            <p><strong>${notification.user}</strong> has requested to learn <strong>${notification.skill}</strong> skill</p>
          </div>
          <div class="notification-actions">
            <button class="accept-btn">Accept</button>
            <button class="decline-btn">Decline</button>
          </div>
        `;
      } else if (notification.type === "memory-reminder") {
        notificationDiv.innerHTML = `
          <div class="notification-content">
            <p>${notification.message}</p>
          </div>
        `;
      } else if (notification.type === "success-message") {
        notificationDiv.classList.add("success-message");
        notificationDiv.innerHTML = `
          <div class="notification-content">
            <p>${notification.message}</p>
          </div>
        `;
      }
  
      notificationsSection.appendChild(notificationDiv);
    });
  }
  
  // Add interactivity to buttons
  document.addEventListener("DOMContentLoaded", () => {
    renderNotifications();
  
    document.querySelectorAll('.accept-btn').forEach(button => {
      button.addEventListener('click', () => {
        alert('Request Accepted!');
      });
    });
  
    document.querySelectorAll('.decline-btn').forEach(button => {
      button.addEventListener('click', () => {
        alert('Request Declined!');
      });
    });
  });