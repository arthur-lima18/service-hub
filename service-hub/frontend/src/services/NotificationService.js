import SockJS from 'sockjs-client';
import Stomp from 'webstomp-client';

let stompClient = null;

export const initNotifications = (onNotification) => {
    const socket = new SockJS('http://localhost:8081/ws-notifications');
    stompClient = Stomp.over(socket);

    stompClient.connect({}, (frame) => {
        console.log('Connected to Java Notification Service: ' + frame);
        
        stompClient.subscribe('/topic/notifications', (message) => {
            if (message.body) {
                const data = JSON.parse(message.body);
                console.log('New real-time notification (Java):', data);
                if (onNotification) {
                    onNotification(data);
                }
            }
        });
    }, (error) => {
        console.error('STOMP error:', error);
    });
};

export default stompClient;
