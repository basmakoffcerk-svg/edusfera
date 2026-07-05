package redis

import (
	"context"
	"fmt"
	"log/slog"
	"time"

	"github.com/redis/go-redis/v9"
)

type RedisClient struct {
	Client *redis.Client
	logger *slog.Logger
}

func Connect(ctx context.Context, addr string, logger *slog.Logger) (*RedisClient, error) {
	rdb := redis.NewClient(&redis.Options{
		Addr: addr,
	})

	err := rdb.Ping(ctx).Err()
	if err != nil {
		return nil, fmt.Errorf("failed to ping redis: %w", err)
	}

	logger.Info("Connected to Redis successfully", "address", addr)
	return &RedisClient{
		Client: rdb,
		logger: logger,
	}, nil
}

func (r *RedisClient) GetState(ctx context.Context, roomId string) (string, error) {
	key := fmt.Sprintf("workspace:%s", roomId)
	val, err := r.Client.Get(ctx, key).Result()
	if err == redis.Nil {
		return "", nil // cache miss
	}
	return val, err
}

func (r *RedisClient) SetState(ctx context.Context, roomId string, stateJSON string, ttl time.Duration) error {
	key := fmt.Sprintf("workspace:%s", roomId)
	return r.Client.Set(ctx, key, stateJSON, ttl).Err()
}

func (r *RedisClient) DeleteState(ctx context.Context, roomId string) error {
	key := fmt.Sprintf("workspace:%s", roomId)
	return r.Client.Del(ctx, key).Err()
}

func (r *RedisClient) PublishEvent(ctx context.Context, roomId string, eventJSON string) error {
	channel := fmt.Sprintf("pubsub:workspace:%s", roomId)
	return r.Client.Publish(ctx, channel, eventJSON).Err()
}

func (r *RedisClient) Subscribe(ctx context.Context, roomId string) *redis.PubSub {
	channel := fmt.Sprintf("pubsub:workspace:%s", roomId)
	return r.Client.Subscribe(ctx, channel)
}

func (r *RedisClient) Close() error {
	return r.Client.Close()
}
