package com.arthur.notificationservice.controller;

import org.springframework.messaging.simp.SimpMessagingTemplate;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.util.Map;

@RestController
@RequestMapping("/notifications")
@CrossOrigin(origins = "*")
public class NotificationController {

    private final SimpMessagingTemplate messagingTemplate;

    public NotificationController(SimpMessagingTemplate messagingTemplate) {
        this.messagingTemplate = messagingTemplate;
    }

    @PostMapping
    public ResponseEntity<String> send(@RequestBody Map<String, Object> payload) {
        System.out.println("🔥 Evento recebido e transmitindo via WebSocket: " + payload);
        
        // Transmite para todos os clientes ouvindo o tópico /topic/notifications
        messagingTemplate.convertAndSend("/topic/notifications", payload);
        
        return ResponseEntity.ok("Notificação recebida e transmitida");
    }
}